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

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50]) ? (int)$_GET['per_page'] : 20;

// 计算偏移量
$offset = ($page - 1) * $records_per_page;

// 获取总记录数
$count_query = "SELECT COUNT(*) as total FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN suppliers s ON p.supplier_id = s.person_id";

// 如果是供应商，只统计其自己的商品
if ($_SESSION['role'] === 'supplier') {
    $count_query .= " WHERE p.supplier_id = :supplier_id";
}

$count_stmt = $db->prepare($count_query);
if ($_SESSION['role'] === 'supplier') {
    $count_stmt->bindParam(':supplier_id', $_SESSION['user_id']);
}
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 计算总页数
$total_pages = ceil($total_records / $records_per_page);
$page = min(max(1, $page), $total_pages); // 确保页码在有效范围内

// 获取商品列表
$query = "SELECT p.*, c.name as category_name, s.company_name as supplier_name
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN suppliers s ON p.supplier_id = s.person_id
          ORDER BY p.created_at DESC
          LIMIT :limit OFFSET :offset";

// 如果是供应商，只显示其自己的商品
if ($_SESSION['role'] === 'supplier') {
    $query .= " WHERE p.supplier_id = :supplier_id";
}

$stmt = $db->prepare($query);

// 绑定参数
if ($_SESSION['role'] === 'supplier') {
    $stmt->bindParam(':supplier_id', $_SESSION['user_id']);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

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

    <!-- 分页控件 -->
    <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="d-flex align-items-center">
            <label class="me-2">每页显示：</label>
            <select class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10条</option>
                <option value="20" <?php echo $records_per_page == 20 ? 'selected' : ''; ?>>20条</option>
                <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50条</option>
            </select>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="商品列表分页">
            <ul class="pagination mb-0">
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

<script>
function changePerPage(value) {
    window.location.href = '?page=1&per_page=' + value;
}
</script>

<?php include '../includes/footer.php'; ?> 