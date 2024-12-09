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

// 获取库存记录
$query = "SELECT ir.*, 
          p.name as product_name,
          op.username as operator_name,
          CASE 
            WHEN e.person_id IS NOT NULL THEN CONCAT(op.username, ' (员工)')
            WHEN s.person_id IS NOT NULL THEN CONCAT(s.company_name, ' (供应商)')
            WHEN op.role = 'admin' THEN CONCAT(op.username, ' (管理员)')
          END as operator_info
          FROM inventory_records ir 
          LEFT JOIN products p ON ir.product_id = p.id 
          JOIN persons op ON ir.operator_id = op.id
          LEFT JOIN employees e ON op.id = e.person_id
          LEFT JOIN suppliers s ON op.id = s.person_id
          ORDER BY ir.created_at DESC 
          LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>库存管理</h2>
        <div>
            <a href="stock_in.php" class="btn btn-success">商品入库</a>
            <a href="stock_out.php" class="btn btn-warning">商品出库</a>
            <a href="stock_check.php" class="btn btn-info">库存盘点</a>
            <a href="report.php" class="btn btn-primary">库存报表</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>最近库存变动记录</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>时间</th>
                        <th>商品</th>
                        <th>操作类型</th>
                        <th>变动数量</th>
                        <th>变动前库存</th>
                        <th>变动后库存</th>
                        <th>操作人</th>
                        <th>备注</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($row['before_quantity']); ?></td>
                        <td><?php echo htmlspecialchars($row['after_quantity']); ?></td>
                        <td><?php echo htmlspecialchars($row['operator_info']); ?></td>
                        <td><?php echo htmlspecialchars($row['remark']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 