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
$order_id = $data['order_id'] ?? null;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '无效的请求参数']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // 检查订单状态
    $query = "SELECT * FROM orders 
              WHERE id = :id AND customer_id = :customer_id 
              AND status = '待付款'
              FOR UPDATE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $order_id);
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('订单不存在或不能取消');
    }
    
    // 获取订单商品
    $query = "SELECT oi.*, p.stock_quantity 
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 恢复商品库存
    foreach ($items as $item) {
        // 更新商品库存
        $new_quantity = $item['stock_quantity'] + $item['quantity'];
        $query = "UPDATE products 
                  SET stock_quantity = :quantity 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':quantity', $new_quantity);
        $stmt->bindParam(':id', $item['product_id']);
        $stmt->execute();
        
        // 记录库存变动
        $query = "INSERT INTO inventory_records 
                  (product_id, type, quantity, before_quantity, after_quantity, operator_id, remark) 
                  VALUES (:product_id, '入库', :quantity, :before_quantity, :after_quantity, :operator_id, :remark)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $item['product_id']);
        $stmt->bindParam(':quantity', $item['quantity']);
        $stmt->bindParam(':before_quantity', $item['stock_quantity']);
        $stmt->bindParam(':after_quantity', $new_quantity);
        $stmt->bindParam(':operator_id', $_SESSION['user_id']);
        $remark = "订单取消入库: " . $order_id;
        $stmt->bindParam(':remark', $remark);
        $stmt->execute();
    }
    
    // 更新订单状态
    $query = "UPDATE orders 
              SET status = '已取消', updated_at = CURRENT_TIMESTAMP 
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $order_id);
    $stmt->execute();
    
    $db->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 