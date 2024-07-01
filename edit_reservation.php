<?php
declare(strict_types=1);

include 'config.php';

// Définition de la fonction getRestaurants
function getRestaurants(PDO $pdo): array {
    $stmt = $pdo->prepare('SELECT id, name, seats FROM restaurants ORDER BY name ASC');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Charger la réservation à modifier
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die('ID de réservation non spécifié.');
}

$reservation_id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $restaurant_id = filter_input(INPUT_POST, 'restaurant', FILTER_VALIDATE_INT);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $couverts = filter_input(INPUT_POST, 'couverts', FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    // Vérifications identiques à la création de réservation
    try {
        $current_date = new DateTimeImmutable();
        $reservation_date = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (!$reservation_date) {
            throw new Exception("Date invalide.");
        }

        $interval = $current_date->diff($reservation_date)->days;

        if ($reservation_date < $current_date || $interval > 60) {
            throw new Exception("La date doit être >= à aujourd'hui et <= 2 mois à l'avance.");
        }

        $stmt = $pdo->prepare('SELECT seats FROM restaurants WHERE id = ?');
        $stmt->execute([$restaurant_id]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($couverts < 1 || $couverts > $restaurant['seats']) {
            throw new Exception("Le nombre de couverts doit être >= 1 et <= au nombre de couverts du restaurant sélectionné.");
        }

        if (!$email || !preg_match('/@(.*?)(\.fr|\.com)$/', $email)) {
            throw new Exception("Adresse email invalide.");
        }

        $stmt = $pdo->prepare('SELECT SUM(couverts) as total FROM reservations WHERE restaurant_id = ? AND date = ? AND id != ?');
        $stmt->execute([$restaurant_id, $date, $reservation_id]);
        $total = $stmt->fetchColumn();

        if ($total + $couverts > $restaurant['seats']) {
            throw new Exception("Désolé pas de place disponible pour cette date.");
        }

        $stmt = $pdo->prepare('UPDATE reservations SET restaurant_id = ?, date = ?, couverts = ?, email = ? WHERE id = ?');
        $stmt->execute([$restaurant_id, $date, $couverts, $email, $reservation_id]);

        echo "Votre réservation a été mise à jour.";
        header('Location: reservations_list.php');
        exit;
    } catch (Exception $e) {
        echo $e->getMessage();
        exit;
    }
} else {
    $stmt = $pdo->prepare('SELECT * FROM reservations WHERE id = ?');
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        die('Réservation non trouvée.');
    }

    $restaurants = getRestaurants($pdo);
}
?>

<!DOCTYPE html>
<html lang="fr">
<link rel="stylesheet" href="index.css">
<head>
    <meta charset="UTF-8">
    <title>Modifier Réservation</title>
</head>
<body>
    <h1>Modifier Réservation</h1>
    <form method="post" action="edit_reservation.php?id=<?= urlencode((string)$reservation_id) ?>">
        <label for="restaurant">Restaurant:</label>
        <select name="restaurant" id="restaurant">
            <?php foreach ($restaurants as $restaurant): ?>
                <option value="<?= htmlspecialchars((string)$restaurant['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $restaurant['id'] == $reservation['restaurant_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br>
        <label for="date">Date:</label>
        <input type="date" name="date" id="date" value="<?= htmlspecialchars($reservation['date'], ENT_QUOTES, 'UTF-8') ?>" required>
        <br>
        <label for="couverts">Nombre de couverts:</label>
        <input type="number" name="couverts" id="couverts" value="<?= htmlspecialchars((string)$reservation['couverts'], ENT_QUOTES, 'UTF-8') ?>" required>
        <br>
        <label for="email">Mon adresse mail:</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($reservation['email'], ENT_QUOTES, 'UTF-8') ?>" required>
        <br>
        <button type="submit" name="modifier">Modifier</button>
    </form>
</body>
</html>
