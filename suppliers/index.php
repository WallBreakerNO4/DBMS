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

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50]) ? (int)$_GET['per_page'] : 20;

// 计算偏移量
$offset = ($page - 1) * $records_per_page;

// 获取总记录数
$count_query = "SELECT COUNT(*) as total FROM persons p 
                JOIN suppliers s ON p.id = s.person_id 
                WHERE p.role = 'supplier'";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 计算总页数
$total_pages = ceil($total_records / $records_per_page);
$page = min(max(1, $page), $total_pages); // 确保页码在有效范围内

// 获取供应商列表
$query = "SELECT p.*, s.*
          FROM persons p
          JOIN suppliers s ON p.id = s.person_id
          WHERE p.role = 'supplier'
          ORDER BY s.company_name
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>供应商管理</h2>
        <a href="/admin/generate_code.php" class="btn btn-primary">生成注册码</a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>公司名称</th>
                        <th>联系人</th>
                        <th>邮箱</th>
                        <th>电话</th>
                        <th>地址</th>
                        <th>注册时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($supplier['id']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['username']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['contact_name']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['address']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['created_at']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo $supplier['id']; ?>" 
                                   class="btn btn-sm btn-primary">编辑</a>
                                <a href="reset_password.php?id=<?php echo $supplier['id']; ?>" 
                                   class="btn btn-sm btn-warning">重置密码</a>
                                <a href="delete.php?id=<?php echo $supplier['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('确定要删除该供应商吗？删除后将无法恢复！')">删除</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
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
                <nav aria-label="供应商列表分页">
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
    </div>
</div>

<script>
function changePerPage(value) {
    window.location.href = '?page=1&per_page=' + value;
}
</script>

<?php include '../includes/footer.php'; ?>