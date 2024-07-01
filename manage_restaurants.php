<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = strtoupper(trim($_POST['name']));
    $city = strtoupper(trim($_POST['city']));
    $seats = (int)$_POST['seats'];

    if (strlen($name) > 50 || strlen($city) > 50 || $seats < 1 || $seats > 20) {
        echo "Données invalides.";
        exit;
    }

    if (isset($_POST['id'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare('SELECT id FROM restaurants WHERE name = ? AND id != ?');
        $stmt->execute([$name, $id]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM restaurants WHERE name = ?');
        $stmt->execute([$name]);
    }

    if ($stmt->fetch()) {
        echo "Ce restaurant existe déjà.";
        exit;
    }

    if (isset($_POST['id'])) {
        $stmt = $pdo->prepare('UPDATE restaurants SET name = ?, city = ?, seats = ?, modifie_le = NOW() WHERE id = ?');
        $stmt->execute([$name, $city, $seats, $_POST['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO restaurants (name, city, seats, cree_le, modifie_le) VALUES (?, ?, ?, NOW(), NOW())');
        $stmt->execute([$name, $city, $seats]);
    }

    header('Location: list_restaurants.php');
    exit;
} elseif (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM restaurants WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $restaurant = ['id' => '', 'name' => '', 'city' => '', 'seats' => ''];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $restaurant['id'] ? 'Modifier' : 'Ajouter' ?> un Restaurant</title>
</head>
<body>
    <h1><?= $restaurant['id'] ? 'Modifier' : 'Ajouter' ?> un Restaurant</h1>
    <form method="post" action="manage_restaurants.php">
        <?php if ($restaurant['id']): ?>
            <input type="hidden" name="id" value="<?= $restaurant['id'] ?>">
        <?php endif; ?>
        <label for="name">Nom du restaurant:</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($restaurant['name']) ?>" maxlength="50" required>
        <br>
        <label for="city">Ville:</label>
        <input type="text" name="city" id="city" value="<?= htmlspecialchars($restaurant['city']) ?>" maxlength="50" required>
        <br>
        <label for="seats">Nombre maximum de couverts par réservation:</label>
        <input type="number" name="seats" id="seats" value="<?= $restaurant['seats'] ?>" min="1" max="20" required>
        <br>
        <button type="submit">Valider</button>
        <button type="button" onclick="window.location.href='list_restaurants.php'">Abandonner</button>
    </form>
</body>
</html>
