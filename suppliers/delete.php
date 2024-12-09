<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是管理员
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(404);
    include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    exit();
}

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // 由于设置了CASCADE，只需要删除persons表的记录
        // suppliers表的记录会自动删除
        $query = "DELETE FROM persons 
                 WHERE id = :id AND role = 'supplier'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        // 记录错误日志
    }
}

header("Location: index.php");
exit();
?>