<?php
include 'config.php';

// Charger la réservation à modifier
if (!isset($_GET['id'])) {
    die('ID de réservation non spécifié.');
}

$reservation_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier'])) {
    $restaurant_id = $_POST['restaurant'];
    $date = $_POST['date'];
    $couverts = $_POST['couverts'];
    $email = $_POST['email'];

    // Vérifications identiques à la création de réservation

    $current_date = new DateTime();
    $reservation_date = DateTime::createFromFormat('Y-m-d', $date);
    $interval = $current_date->diff($reservation_date)->days;

    if ($reservation_date < $current_date || $interval > 60) {
        echo "La date doit être >= à aujourd'hui et <= 2 mois à l'avance.";
        exit;
    }

    $stmt = $pdo->prepare('SELECT seats FROM restaurants WHERE id = ?');
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($couverts < 1 || $couverts > $restaurant['seats']) {
        echo "Le nombre de couverts doit être >= 1 et <= au nombre de couverts du restaurant sélectionné.";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@(.*?)(\.fr|\.com)$/', $email)) {
        echo "Adresse email invalide.";
        exit;
    }

    $stmt = $pdo->prepare('SELECT SUM(couverts) as total FROM reservations WHERE restaurant_id = ? AND date = ? AND id != ?');
    $stmt->execute([$restaurant_id, $date, $reservation_id]);
    $total = $stmt->fetchColumn();

    if ($total + $couverts > $restaurant['seats']) {
        echo "Désolé pas de place disponible pour cette date.";
        exit;
    }

    $stmt = $pdo->prepare('UPDATE reservations SET restaurant_id = ?, date = ?, couverts = ?, email = ? WHERE id = ?');
    $stmt->execute([$restaurant_id, $date, $couverts, $email, $reservation_id]);

    echo "Votre réservation a été mise à jour.";
    header('Location: reservations_list.php');
    exit;
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
<head>
    <meta charset="UTF-8">
    <title>Modifier Réservation</title>
</head>
<body>
    <h1>Modifier Réservation</h1>
    <form method="post" action="edit_reservation.php?id=<?= $reservation_id ?>">
        <label for="restaurant">Restaurant:</label>
        <select name="restaurant" id="restaurant">
            <?php foreach ($restaurants as $restaurant): ?>
                <option value="<?= $restaurant['id'] ?>" <?= $restaurant['id'] == $reservation['restaurant_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($restaurant['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br>
        <label for="date">Date:</label>
        <input type="date" name="date" id="date" value="<?= $reservation['date'] ?>" required>
        <br>
        <label for="couverts">Nombre de couverts:</label>
        <input type="number" name="couverts" id="couverts" value="<?= $reservation['couverts'] ?>" required>
        <br>
        <label for="email">Mon adresse mail:</label>
        <input type="email" name="email" id="email" value="<?= $reservation['email'] ?>" required>
        <br>
        <button type="submit" name="modifier">Modifier</button>
    </form>
</body>
</html>
