<?php
session_start();
require_once '../../config/database.php';

// 检查用户是否登录且是管理员
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => '无权访问']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // 获取符合搜索条件的供应商总数
    $count_query = "SELECT COUNT(*) as total 
                    FROM persons p 
                    JOIN suppliers s ON p.id = s.person_id 
                    WHERE p.role = 'supplier' 
                    AND (s.company_name LIKE :search 
                         OR p.username LIKE :search)";
    $stmt = $db->prepare($count_query);
    $search_param = "%{$search}%";
    $stmt->bindParam(':search', $search_param);
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 获取分页后的供应商列表
    $query = "SELECT p.id, s.company_name, p.username
              FROM persons p 
              JOIN suppliers s ON p.id = s.person_id 
              WHERE p.role = 'supplier' 
              AND (s.company_name LIKE :search 
                   OR p.username LIKE :search)
              ORDER BY s.company_name
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':search', $search_param);
    $stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'suppliers' => $suppliers,
        'has_more' => ($offset + $per_page) < $total
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '服务器错误']);
} 