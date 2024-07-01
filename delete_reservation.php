<?php
declare(strict_types=1);

include 'config.php';

// Annuler la réservation
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die('ID de réservation non spécifié ou invalide.');
}

$reservation_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare('DELETE FROM reservations WHERE id = ?');
    $stmt->execute([$reservation_id]);

    header('Location: reservations_list.php');
    exit;
} catch (Exception $e) {
    echo "Erreur lors de l'annulation de la réservation : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>
