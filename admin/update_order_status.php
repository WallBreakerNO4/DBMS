<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是管理员或员工
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'employee'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '无权限执行此操作']);
    exit();
}

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;
$status = $data['status'] ?? null;

if (!$order_id || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '无效的请求参数']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // 检查订单状态
    $query = "SELECT status FROM orders WHERE id = :id FOR UPDATE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $order_id);
    $stmt->execute();
    $current_status = $stmt->fetch(PDO::FETCH_ASSOC)['status'];
    
    // 验证状态转换的合法性
    $valid = match($current_status) {
        '已付款' => $status === '已发货',
        '已发货' => $status === '已完成',
        default => false
    };
    
    if (!$valid) {
        throw new Exception('非法的状态转换');
    }
    
    // 更新订单状态
    $query = "UPDATE orders SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $order_id);
    $stmt->execute();
    
    $db->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 