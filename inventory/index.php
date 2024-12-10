<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是管理员、供应商或员工
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supplier', 'employee'])) {
    header("Location: /auth/login.php");
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 获取每页显示记录数
$allowed_records = [20, 50, 100];
$records_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $allowed_records) 
    ? (int)$_GET['per_page'] 
    : 20;

// 获取当前页码
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // 确保页码至少为1

// 计算偏移量
$offset = ($page - 1) * $records_per_page;

// 获取总记录数
$count_query = "SELECT COUNT(*) as total FROM inventory_records";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 计算总页数
$total_pages = ceil($total_records / $records_per_page);
$page = min($page, $total_pages); // 确保页码不超过最大页数

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
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>库存管理</h2>
        <div>
            <a href="stock_in.php" class="btn btn-success">商品入库</a>
            <a href="stock_out.php" class="btn btn-warning">商品出库</a>
            <?php if ($_SESSION['role'] !== 'supplier'): ?>
            <a href="stock_check.php" class="btn btn-info">库存盘点</a>
            <?php endif; ?>
            <a href="report.php" class="btn btn-primary">库存报表</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">最近库存变动记录</h3>
            <div class="d-flex align-items-center">
                <label class="me-2">每页显示：</label>
                <select class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                    <option value="20" <?php echo $records_per_page == 20 ? 'selected' : ''; ?>>20条</option>
                    <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50条</option>
                    <option value="100" <?php echo $records_per_page == 100 ? 'selected' : ''; ?>>100条</option>
                </select>
            </div>
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

            <!-- 分页导航 -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="库存记录分页" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- 首页 -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=1&per_page=<?php echo $records_per_page; ?>">首页</a>
                    </li>
                    
                    <!-- 上一页 -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $records_per_page; ?>">上一页</a>
                    </li>

                    <!-- 页码 -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $records_per_page; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>

                    <!-- 下一页 -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $records_per_page; ?>">下一页</a>
                    </li>

                    <!-- 末页 -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&per_page=<?php echo $records_per_page; ?>">末页</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changePerPage(value) {
    window.location.href = '?page=1&per_page=' + value;
}
</script>

<?php include '../includes/footer.php'; ?> 