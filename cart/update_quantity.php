<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是顾客
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? null;
$quantity = $data['quantity'] ?? null;

if (!$item_id || !$quantity || $quantity < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '无效的请求参数']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // 检查购物车项是否属于当前用户
    $query = "SELECT ci.*, p.stock_quantity 
              FROM cart_items ci
              JOIN products p ON ci.product_id = p.id
              WHERE ci.id = :id AND ci.customer_id = :customer_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $item_id);
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->execute();
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart_item) {
        throw new Exception('购物车项不存在');
    }
    
    if ($quantity > $cart_item['stock_quantity']) {
        throw new Exception('库存不足');
    }
    
    // 更新数量
    $query = "UPDATE cart_items 
              SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP 
              WHERE id = :id AND customer_id = :customer_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':id', $item_id);
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 