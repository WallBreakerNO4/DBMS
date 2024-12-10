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
$product_id = $data['product_id'] ?? null;
$quantity = $data['quantity'] ?? null;

if (!$product_id || !$quantity || $quantity < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '无效的请求参数']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // 检查商品是否存在且有足够库存
    $query = "SELECT stock_quantity FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('商品不存在');
    }
    
    if ($product['stock_quantity'] < $quantity) {
        throw new Exception('库存��足');
    }
    
    // 检查购物车是否已有该商品
    $query = "SELECT id, quantity FROM cart_items 
              WHERE customer_id = :customer_id AND product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cart_item) {
        // 更新现有购物车项
        $new_quantity = $cart_item['quantity'] + $quantity;
        if ($new_quantity > $product['stock_quantity']) {
            throw new Exception('库存不足');
        }
        
        $query = "UPDATE cart_items 
                  SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':quantity', $new_quantity);
        $stmt->bindParam(':id', $cart_item['id']);
        $stmt->execute();
    } else {
        // 添加新的购物车项
        $query = "INSERT INTO cart_items (customer_id, product_id, quantity) 
                  VALUES (:customer_id, :product_id, :quantity)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_id', $_SESSION['user_id']);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 