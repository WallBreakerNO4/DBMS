<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是管理员或供应商
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'supplier')) {
    http_response_code(404);
    include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    exit();
}

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // 如果是供应商，确保只能删除自己的商品
        $query = "SELECT id FROM products 
                 WHERE id = :id" . 
                 ($_SESSION['role'] === 'supplier' ? " AND supplier_id = :supplier_id" : "");
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        if ($_SESSION['role'] === 'supplier') {
            $stmt->bindParam(':supplier_id', $_SESSION['user_id']);
        }
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            throw new Exception("无权删除该商品");
        }
        
        // 删除商品相关的库存记录
        $query = "DELETE FROM inventory_records WHERE product_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        // 删除商品
        $query = "DELETE FROM products WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        $db->commit();
        header("Location: index.php?deleted=1");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        header("Location: index.php?error=" . urlencode("删除失败: " . $e->getMessage()));
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?> 