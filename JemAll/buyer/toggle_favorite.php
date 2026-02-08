<?php
/**
 * Toggle Favorite - AJAX endpoint
 */
require_once '../config/config.php';
requireLogin();

if (!hasRole('buyer')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$pdo = getDB();
$buyer_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    // Check if already favorited
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE buyer_id = ? AND product_id = ?");
    $stmt->execute([$buyer_id, $product_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE buyer_id = ? AND product_id = ?");
        if ($stmt->execute([$buyer_id, $product_id])) {
            echo json_encode(['success' => true, 'favorited' => false, 'message' => 'Retiré des favoris']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO favorites (buyer_id, product_id) VALUES (?, ?)");
        if ($stmt->execute([$buyer_id, $product_id])) {
            echo json_encode(['success' => true, 'favorited' => true, 'message' => 'Ajouté aux favoris']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout']);
        }
    }
} catch (PDOException $e) {
    error_log('Database error in toggle_favorite: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
