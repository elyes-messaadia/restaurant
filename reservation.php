<?php
declare(strict_types=1);

// reservation.php
include 'config.php';

// Charger la liste des restaurants
function getRestaurants(PDO $pdo): array {
    $stmt = $pdo->prepare('SELECT id, name, seats FROM restaurants ORDER BY name ASC');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$restaurants = getRestaurants($pdo);

// Vérifier si des restaurants existent
if (!$restaurants) {
    die("Aucun restaurant trouvé dans la base de données.");
}

$errors = [];

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider'])) {
    $restaurant_id = filter_input(INPUT_POST, 'restaurant', FILTER_VALIDATE_INT);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $couverts = filter_input(INPUT_POST, 'couverts', FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    // Vérifier la date
    try {
        $current_date = new DateTimeImmutable();
        $reservation_date = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (!$reservation_date) {
            $errors[] = "Date invalide.";
        } else {
            $interval = $current_date->diff($reservation_date)->days;

            if ($reservation_date < $current_date) {
                $errors[] = "Tu veux remonter dans le temps ?!";
            } elseif ($interval > 60) {
                $errors[] = "La date doit être <= 2 mois à l'avance.";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Erreur lors de la vérification de la date.";
    }

    // Vérifier le nombre de couverts
    if ($restaurant_id && $couverts) {
        if ($couverts > 20) {
            $errors[] = "Hey doucement mon gourmand ! On ne peut pas réserver plus de 20 couverts !";
        } else {
            $stmt = $pdo->prepare('SELECT seats FROM restaurants WHERE id = ?');
            $stmt->execute([$restaurant_id]);
            $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$restaurant) {
                $errors[] = "Restaurant non trouvé.";
            } elseif ($couverts < 1 || $couverts > $restaurant['seats']) {
                $errors[] = "Le nombre de couverts doit être >= 1 et <= au nombre de couverts du restaurant sélectionné.";
            }
        }
    } else {
        $errors[] = "Sélection de restaurant ou nombre de couverts invalide.";
    }

    // Vérifier l'adresse email
    if (!$email) {
        $errors[] = "Adresse email invalide.";
    } elseif (strpos($email, ' ') !== false) {
        $errors[] = "L'adresse email ne doit pas contenir d'espaces.";
    }

    // Vérifier la disponibilité des places à la date sélectionnée
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT SUM(couverts) as total FROM reservations WHERE restaurant_id = ? AND date = ?');
        $stmt->execute([$restaurant_id, $date]);
        $total = $stmt->fetchColumn();

        if ($total + $couverts > $restaurant['seats']) {
            $errors[] = "Désolé, pas de place disponible pour cette date.";
        }
    }

    // Insérer la réservation dans la base de données
    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO reservations (restaurant_id, date, couverts, email) VALUES (?, ?, ?, ?)');
        $stmt->execute([$restaurant_id, $date, $couverts, $email]);

        echo "<p>Merci, votre réservation a bien été prise en compte.</p>";
        header('Location: reservations_list.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<link rel="stylesheet" href="index.css">
<head>
    <meta charset="UTF-8">
    <title>Réserver une table au restaurant</title>
</head>
<body>
    <h1>Réserver une table au restaurant</h1>
    <?php if (!empty($errors)): ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" action="reservation.php">
        <label for="restaurant">Restaurant:</label>
        <select name="restaurant" id="restaurant" required>
            <?php foreach ($restaurants as $restaurant): ?>
                <option value="<?= htmlspecialchars((string)$restaurant['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <label for="date">Date:</label>
        <input type="date" name="date" id="date" required>
        <br>
        <label for="couverts">Nombre de couverts:</label>
        <input type="number" name="couverts" id="couverts" required>
        <br>
        <label for="email">Mon adresse mail:</label>
        <input type="email" name="email" id="email" required>
        <br>
        <button type="submit" name="valider">Valider</button>
        <button type="button" onclick="window.location.href='reservations_list.php'">Abandonner</button>
    </form>
</body>
</html>
