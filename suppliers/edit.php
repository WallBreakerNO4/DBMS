<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是管理员
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(404);
    include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 获取供应商信息
if (isset($_GET['id'])) {
    $query = "SELECT p.*, s.*
              FROM persons p
              JOIN suppliers s ON p.id = s.person_id
              WHERE p.id = :id AND p.role = 'supplier'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$supplier) {
        header("Location: index.php");
        exit();
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();
        
        // 更新persons表
        $query = "UPDATE persons 
                 SET email = :email,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        // 更新suppliers表
        $query = "UPDATE suppliers 
                 SET company_name = :company_name,
                     contact_name = :contact_name,
                     phone = :phone,
                     address = :address
                 WHERE person_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':company_name', $_POST['company_name']);
        $stmt->bindParam(':contact_name', $_POST['contact_name']);
        $stmt->bindParam(':phone', $_POST['phone']);
        $stmt->bindParam(':address', $_POST['address']);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        $db->commit();
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = "更新失败: " . $e->getMessage();
    }
}
?>