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

// 获取供应商列表
$query = "SELECT p.*, s.*
          FROM persons p
          JOIN suppliers s ON p.id = s.person_id
          WHERE p.role = 'supplier'
          ORDER BY s.company_name";
$stmt = $db->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- 其余HTML代码保持不变 -->