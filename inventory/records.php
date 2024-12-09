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
          c.name as category_name,
          op.username as operator_name,
          CASE 
            WHEN e.person_id IS NOT NULL THEN CONCAT(op.username, ' (员工)')
            WHEN s.person_id IS NOT NULL THEN CONCAT(s.company_name, ' (供应商)')
            WHEN op.role = 'admin' THEN CONCAT(op.username, ' (管理员)')
          END as operator_info
          FROM inventory_records ir
          JOIN products p ON ir.product_id = p.id
          LEFT JOIN categories c ON p.category_id = c.id
          JOIN persons op ON ir.operator_id = op.id
          LEFT JOIN employees e ON op.id = e.person_id
          LEFT JOIN suppliers s ON op.id = s.person_id
          ORDER BY ir.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3>库存变动记录</h3>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>时间</th>
                                <th>商品</th>
                                <th>类别</th>
                                <th>操作类型</th>
                                <th>变动数量</th>
                                <th>变动前库存</th>
                                <th>变动后库存</th>
                                <th>操作人</th>
                                <th>备注</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($record['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['type']); ?></td>
                                <td><?php echo htmlspecialchars($record['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($record['before_quantity']); ?></td>
                                <td><?php echo htmlspecialchars($record['after_quantity']); ?></td>
                                <td><?php echo htmlspecialchars($record['operator_info']); ?></td>
                                <td><?php echo htmlspecialchars($record['remark']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 