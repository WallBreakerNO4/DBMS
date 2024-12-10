<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是管理员
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(404);
    include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (isset($_GET['id'])) {
    try {
        $db->beginTransaction();
        
        // 删除persons表中的记录（employees表的记录会因为外键级联删除而自动删除）
        $query = "DELETE FROM persons WHERE id = :id AND role = 'employee'";
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