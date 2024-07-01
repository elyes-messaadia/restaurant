<?php
include 'config.php';

// Annuler la réservation
if (!isset($_GET['id'])) {
    die('ID de réservation non spécifié.');
}

$reservation_id = $_GET['id'];

$stmt = $pdo->prepare('DELETE FROM reservations WHERE id = ?');
$stmt->execute([$reservation_id]);

header('Location: reservations_list.php');
exit;
?>
