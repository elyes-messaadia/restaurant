<?php
// reservation.php
include 'config.php';

// Charger la liste des restaurants
function getRestaurants($pdo) {
    $stmt = $pdo->prepare('SELECT id, name, seats FROM restaurants ORDER BY name ASC');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$restaurants = getRestaurants($pdo);

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['valider'])) {
    $restaurant_id = $_POST['restaurants'];
    $date = $_POST['date'];
    $couverts = $_POST['couverts'];
    $email = $_POST['email'];

    // Vérifier la date
    $current_date = new DateTime();
    $reservation_date = DateTime::createFromFormat('Y-m-d', $date);
    $interval = $current_date->diff($reservation_date)->days;

    if ($reservation_date < $current_date || $interval > 60) {
        echo "La date doit être >= à aujourd'hui et <= 2 mois à l'avance.";
        exit;
    }

    // Vérifier le nombre de couverts
    $stmt = $pdo->prepare('SELECT seats FROM restaurants WHERE id = ?');
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($restaurant_id);
    if ($couverts < 1 || $couverts > $restaurant['seats']) {
        echo "Le nombre de couverts doit être >= 1 et <= au nombre de couverts du restaurant sélectionné.";
        exit;
    }

    // Vérifier l'adresse email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@(.*?)(\.fr|\.com)$/', $email)) {
        echo "Adresse email invalide.";
        exit;
    }

    // Vérifier la disponibilité des places à la date sélectionnée
    $stmt = $pdo->prepare('SELECT SUM(couverts) as total FROM reservations WHERE restaurant_id = ? AND date = ?');
    $stmt->execute([$restaurant_id, $date]);
    $total = $stmt->fetchColumn();

    if ($total + $couverts > $restaurant['seats']) {
        echo "Désolé pas de place disponible pour cette date.";
        exit;
    }

    // Insérer la réservation dans la base de données
    $stmt = $pdo->prepare('INSERT INTO reservations (restaurant_id, date, couverts, email) VALUES (?, ?, ?, ?)');
    $stmt->execute([$restaurant_id, $date, $couverts, $email]);

    echo "Merci, votre réservation a bien été prise en compte";
    // Retourner à la liste des restaurants
    header('Location: reservations_list.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réserver une table au restaurant</title>
</head>
<body>
    <h1>Réserver une table au restaurant</h1>
    <form method="post" action="reservation.php">
        <label for="restaurant">Restaurant:</label>
        <select name="restaurant" id="restaurant">
            <?php foreach ($restaurants as $restaurant): ?>
                <option value="<?= $restaurant['id'] ?>"><?= htmlspecialchars($restaurant['name']) ?></option>
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
