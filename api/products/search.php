<?php
session_start();
require_once '../../config/database.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
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
    // 基础查询
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE (p.name LIKE :search 
                    OR c.name LIKE :search 
                    OR p.id LIKE :search)";
    
    // 如果是供应商，只显示其自己的商品
    if ($_SESSION['role'] === 'supplier') {
        $query .= " AND p.supplier_id = :supplier_id";
    }
    
    // 如果是出库页面的搜索，只显示有库存的商品
    if (isset($_GET['stock_out']) && $_GET['stock_out'] === 'true') {
        $query .= " AND p.stock_quantity > 0";
    }
    
    $query .= " ORDER BY p.name LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $search_param = "%{$search}%";
    $stmt->bindParam(':search', $search_param);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    if ($_SESSION['role'] === 'supplier') {
        $stmt->bindParam(':supplier_id', $_SESSION['user_id']);
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'products' => $products,
        'has_more' => count($products) === $per_page
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 