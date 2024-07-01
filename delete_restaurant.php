<?php
include 'config.php';

// Supprimer le restaurant
if (!isset($_GET['id'])) {
    die('ID de restaurant non spécifié.');
}

$restaurant_id = $_GET['id'];

// Vérifier qu'aucune réservation en cours
$stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE restaurant_id = ?');
$stmt->execute([$restaurant_id]);
$reservations_count = $stmt->fetchColumn();

if ($reservations_count > 0) {
    echo "Impossible de supprimer le restaurant car il y a des réservations en cours.";
    exit;
}

// Supprimer les réservations passées
$stmt = $pdo->prepare('DELETE FROM reservations WHERE restaurant_id = ?');
$stmt->execute([$restaurant_id]);

// Supprimer le restaurant
$stmt = $pdo->prepare('DELETE FROM restaurants WHERE id = ?');
$stmt->execute([$restaurant_id]);

header('Location: list_restaurants.php');
exit;
?>
