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
    $db->beginTransaction();
    
    // 检查商品是否存在且有足够库存
    $query = "SELECT price, stock_quantity FROM products WHERE id = :id FOR UPDATE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('商品不存在');
    }
    
    if ($product['stock_quantity'] < $quantity) {
        throw new Exception('库存不足');
    }
    
    // 创建订单
    $query = "INSERT INTO orders (customer_id, total_amount) VALUES (:customer_id, :total_amount)";
    $stmt = $db->prepare($query);
    $total_amount = $product['price'] * $quantity;
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->bindParam(':total_amount', $total_amount);
    $stmt->execute();
    $order_id = $db->lastInsertId();
    
    // 创建订单项
    $query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
              VALUES (:order_id, :product_id, :quantity, :price)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':price', $product['price']);
    $stmt->execute();
    
    // 更新商品库存
    $new_quantity = $product['stock_quantity'] - $quantity;
    $query = "UPDATE products SET stock_quantity = :quantity WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':quantity', $new_quantity);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    
    // 记录库存变动
    $query = "INSERT INTO inventory_records (product_id, type, quantity, before_quantity, after_quantity, operator_id, remark) 
              VALUES (:product_id, '出库', :quantity, :before_quantity, :after_quantity, :operator_id, :remark)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':before_quantity', $product['stock_quantity']);
    $stmt->bindParam(':after_quantity', $new_quantity);
    $stmt->bindParam(':operator_id', $_SESSION['user_id']);
    $remark = "订单出库: " . $order_id;
    $stmt->bindParam(':remark', $remark);
    $stmt->execute();
    
    $db->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 