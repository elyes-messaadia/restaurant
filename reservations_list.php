<?php
include 'config.php';

// Charger la liste des réservations
function getReservations($pdo) {
    $stmt = $pdo->prepare('SELECT r.id, r.date, r.couverts, r.email, res.name as restaurant_name FROM reservations r JOIN restaurants res ON r.restaurant_id = res.id ORDER BY r.date ASC');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$reservations = getReservations($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Réservations</title>
</head>
<body>
    <h1>Liste des Réservations</h1>
    <table border="1">
        <tr>
            <th>Restaurant</th>
            <th>Date</th>
            <th>Nombre de couverts</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($reservations as $reservation): ?>
        <tr>
            <td><?= htmlspecialchars($reservation['restaurant_name']) ?></td>
            <td><?= htmlspecialchars($reservation['date']) ?></td>
            <td><?= htmlspecialchars($reservation['couverts']) ?></td>
            <td><?= htmlspecialchars($reservation['email']) ?></td>
            <td>
                <a href="edit_reservation.php?id=<?= $reservation['id'] ?>">Modifier</a>
                <a href="delete_reservation.php?id=<?= $reservation['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">Annuler</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <br>
    <button type="button" onclick="window.location.href='reservation.php'">Ajouter une nouvelle réservation</button>
</body>
</html>
