<?php
declare(strict_types=1);

include 'config.php';

function validateRestaurantData(string $name, string $city, int $seats): void {
    if (strlen($name) > 50 || strlen($city) > 50 || $seats < 1 || $seats > 20) {
        throw new InvalidArgumentException("Données invalides.");
    }
}

function checkIfRestaurantExists(PDO $pdo, string $name, ?int $id): bool {
    if ($id) {
        $stmt = $pdo->prepare('SELECT id FROM restaurants WHERE name = ? AND id != ?');
        $stmt->execute([$name, $id]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM restaurants WHERE name = ?');
        $stmt->execute([$name]);
    }
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

// Initialisation des variables
$message = '';
$restaurant = ['id' => '', 'name' => '', 'city' => '', 'seats' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = strtoupper(trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
    $city = strtoupper(trim(filter_input(INPUT_POST, 'city', FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
    $seats = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ["options" => ["default" => null]]);

    try {
        validateRestaurantData($name, $city, $seats);

        if (checkIfRestaurantExists($pdo, $name, $id)) {
            $message = "Ce restaurant existe déjà.";
        } else {
            saveRestaurant($pdo, $name, $city, $seats, $id);
            $message = $id ? "Restaurant mis à jour avec succès." : "Restaurant ajouté avec succès.";
            $restaurant = ['id' => '', 'name' => '', 'city' => '', 'seats' => '']; // Réinitialiser le formulaire
        }
    } catch (InvalidArgumentException $e) {
        $message = $e->getMessage();
    }
} elseif (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM restaurants WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Charger la liste des restaurants
function getRestaurants(PDO $pdo): array {
    $stmt = $pdo->prepare('SELECT id, name, city FROM restaurants ORDER BY name ASC');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$restaurants = getRestaurants($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <link rel="stylesheet" href="list_restaurants.css">
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($restaurant['id'] ? 'Modifier' : 'Ajouter', ENT_QUOTES, 'UTF-8') ?> un Restaurant</title>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($restaurant['id'] ? 'Modifier' : 'Ajouter', ENT_QUOTES, 'UTF-8') ?> un Restaurant</h1>
        <?php if ($message): ?>
            <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
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
            <button type="submit">Valider</button>
            <button type="button" onclick="window.location.href='manage_restaurants.php'">Abandonner</button>
        </form>

        <h1>Liste des Restaurants</h1>
        <table border="1">
            <tr>
                <th>Nom du restaurant</th>
                <th>Ville</th>
                <th>Actions</th>
            </tr>
            <?php if (empty($restaurants)): ?>
            <tr>
                <td colspan="3">Aucun restaurant trouvé</td>
            </tr>
            <?php else: ?>
            <?php foreach ($restaurants as $restaurantItem): ?>
            <tr>
                <td><?= htmlspecialchars($restaurantItem['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($restaurantItem['city'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <a href="manage_restaurants.php?id=<?= urlencode((string)$restaurantItem['id']) ?>">Modifier</a>
                    <a href="delete_restaurant.php?id=<?= urlencode((string)$restaurantItem['id']) ?>" onclick="return confirm('Confirmez-vous la suppression du restaurant <?= htmlspecialchars($restaurantItem['name'], ENT_QUOTES, 'UTF-8') ?> ?');">Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </table>
        <button type="button" onclick="window.location.href='manage_restaurants.php'">Ajouter</button>
    </div>
</body>
</html>

