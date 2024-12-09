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
$query = "SELECT ir.*, p.name as product_name, u.username as operator_name 
          FROM inventory_records ir 
          LEFT JOIN products p ON ir.product_id = p.id 
          LEFT JOIN users u ON ir.operator_id = u.id 
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

    <div class="card mb-4">
        <div class="card-header">
            <h5>库存记录</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>时间</th>
                        <th>商品</th>
                        <th>类型</th>
                        <th>数量变化</th>
                        <th>变更前数量</th>
                        <th>变更后数量</th>
                        <th>操作人</th>
                        <th>备注</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $row['type'] == '入库' ? 'success' : 
                                    ($row['type'] == '出库' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo htmlspecialchars($row['type']); ?>
                            </span>
                        </td>
                        <td><?php echo ($row['type'] == '出库' ? '-' : '+') . $row['quantity']; ?></td>
                        <td><?php echo $row['before_quantity']; ?></td>
                        <td><?php echo $row['after_quantity']; ?></td>
                        <td><?php echo htmlspecialchars($row['operator_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['remark']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 