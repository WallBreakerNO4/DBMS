<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是顾客
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // 获取购物车商品
    $query = "SELECT ci.*, p.price, p.stock_quantity 
              FROM cart_items ci
              JOIN products p ON ci.product_id = p.id
              WHERE ci.customer_id = :customer_id
              FOR UPDATE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->execute();
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cart_items)) {
        throw new Exception('购物车是空的');
    }
    
    // 计算总金额并检查库存
    $total_amount = 0;
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock_quantity']) {
            throw new Exception("商品库存不足");
        }
        $total_amount += $item['price'] * $item['quantity'];
    }
    
    // 创建订单
    $query = "INSERT INTO orders (customer_id, total_amount) 
              VALUES (:customer_id, :total_amount)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->bindParam(':total_amount', $total_amount);
    $stmt->execute();
    $order_id = $db->lastInsertId();
    
    // 创建订单项
    foreach ($cart_items as $item) {
        $query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                  VALUES (:order_id, :product_id, :quantity, :price)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':product_id', $item['product_id']);
        $stmt->bindParam(':quantity', $item['quantity']);
        $stmt->bindParam(':price', $item['price']);
        $stmt->execute();
    }
    
    // 清空购物车
    $query = "DELETE FROM cart_items WHERE customer_id = :customer_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $db->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 