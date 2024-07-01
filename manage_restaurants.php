<?php
declare(strict_types=1);

include 'config.php';

const ERR_INVALID_DATA = "Données invalides.";
const ERR_RESTAURANT_EXISTS = "Ce restaurant existe déjà.";

function validateRestaurantData(string $name, string $city, int $seats): void {
    if (strlen($name) > 50 || strlen($city) > 50 || $seats < 1 || $seats > 20) {
        throw new InvalidArgumentException(ERR_INVALID_DATA);
    }
}

function checkIfRestaurantExists(PDO $pdo, string $name, ?int $id): bool {
    $sql = 'SELECT id FROM restaurants WHERE name = ?' . ($id ? ' AND id != ?' : '');
    $stmt = $pdo->prepare($sql);
    $stmt->execute($id ? [$name, $id] : [$name]);
    return (bool) $stmt->fetch();
}

function saveRestaurant(PDO $pdo, string $name, string $city, int $seats, ?int $id): void {
    if ($id) {
        $stmt = $pdo->prepare('UPDATE restaurants SET name = ?, city = ?, seats = ?, modifie_le = NOW() WHERE id = ?');
        $stmt->execute([$name, $city, $seats, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO restaurants (name, city, seats, cree_le, modifie_le) VALUES (?, ?, ?, NOW(), NOW())');
        $stmt->execute([$name, $city, $seats]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = strtoupper(trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
    $city = strtoupper(trim(filter_input(INPUT_POST, 'city', FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
    $seats = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);

    try {
        validateRestaurantData($name, $city, $seats);

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ["options" => ["default" => null]]);

        if (checkIfRestaurantExists($pdo, $name, $id)) {
            echo ERR_RESTAURANT_EXISTS;
            exit;
        }

        saveRestaurant($pdo, $name, $city, $seats, $id);

        header('Location: list_restaurants.php');
        exit;
    } catch (InvalidArgumentException $e) {
        echo $e->getMessage();
        exit;
    }
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="index.css">
    <title><?= htmlspecialchars($restaurant['id'] ? 'Modifier' : 'Ajouter', ENT_QUOTES, 'UTF-8') ?> un Restaurant</title>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($restaurant['id'] ? 'Modifier' : 'Ajouter', ENT_QUOTES, 'UTF-8') ?> un Restaurant</h1>
        <form method="post" action="manage_restaurants.php">
            <?php if ($restaurant['id']): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$restaurant['id'], ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <label for="name">Nom du restaurant:</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?>" maxlength="50" required>
            <label for="city">Ville:</label>
            <input type="text" name="city" id="city" value="<?= htmlspecialchars($restaurant['city'], ENT_QUOTES, 'UTF-8') ?>" maxlength="50" required>
            <label for="seats">Nombre maximum de couverts par réservation:</label>
            <input type="number" name="seats" id="seats" value="<?= htmlspecialchars((string)$restaurant['seats'], ENT_QUOTES, 'UTF-8') ?>" min="1" max="20" required>
            <div class="button-group">
                <button type="submit">Valider</button>
                <button type="button" onclick="window.location.href='list_restaurants.php'">Abandonner</button>
            </div>
        </form>
    </div>
</body>
</html>
