<?php
/**
 * Read and Remove Notification
 * Marks a notification as read (by deleting it as requested) and redirects
 */
require_once 'config/config.php';
requireLogin();

$notif_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($notif_id > 0) {
    $pdo = getDB();
    
    // 1. Get the link before deleting
    $stmt = $pdo->prepare("SELECT link FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);
    $link = $stmt->fetchColumn();
    
    // 2. Delete the notification (as requested: "suprime le message")
    $delete = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $delete->execute([$notif_id, $user_id]);
    
    // 3. Redirect to the link or notifications page
    if ($link) {
        header("Location: " . BASE_URL . $link);
    } else {
        header("Location: " . BASE_URL . "notifications.php");
    }
    exit;
}

header("Location: " . BASE_URL . "index.php");
exit;
