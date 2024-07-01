<?php
include 'config.php';

// Charger la liste des restaurants
function getRestaurants($pdo) {
    $stmt = $pdo->prepare('SELECT id, name, city FROM restaurants ORDER BY name ASC');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$restaurants = getRestaurants($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Restaurants</title>
</head>
<body>
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
        <?php foreach ($restaurants as $restaurant): ?>
        <tr>
            <td><?= htmlspecialchars($restaurant['name']) ?></td>
            <td><?= htmlspecialchars($restaurant['city']) ?></td>
            <td>
                <a href="manage_restaurants.php?id=<?= $restaurant['id'] ?>">Modifier</a>
                <a href="delete_restaurant.php?id=<?= $restaurant['id'] ?>" onclick="return confirm('Confirmez-vous la suppression du restaurant <?= htmlspecialchars($restaurant['name']) ?> ?');">Supprimer</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </table>
    <br>
    <button type="button" onclick="window.location.href='manage_restaurants.php'">Ajouter</button>
</body>
</html>
