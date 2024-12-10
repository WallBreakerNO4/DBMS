<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 获取商品列表
$query = "SELECT p.*, c.name as category_name, s.company_name as supplier_name
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN suppliers s ON p.supplier_id = s.person_id";

// 如果是供应商，只显示其自己的商品
if ($_SESSION['role'] === 'supplier') {
    $query .= " WHERE p.supplier_id = :supplier_id";
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);

// 如果是供应商，绑定supplier_id参数
if ($_SESSION['role'] === 'supplier') {
    $stmt->bindParam(':supplier_id', $_SESSION['user_id']);
}

$stmt->execute();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>商品管理</h2>
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'supplier'): ?>
        <a href="create.php" class="btn btn-primary">添加商品</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>商品名称</th>
                        <th>类别</th>
                        <th>价格</th>
                        <th>库存</th>
                        <th>供应商</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td>￥<?php echo number_format($row['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['stock_quantity']); ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name'] ?? '未指定'); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">编辑</a>
                            <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirm('确定要删除这个商品吗？')">删除</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 