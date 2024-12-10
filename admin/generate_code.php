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

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // 生成注册码
        $code = bin2hex(random_bytes(16));
        
        // 保存注册码
        $query = "INSERT INTO registration_codes (code, created_by) 
                 VALUES (:code, :created_by)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);
        $stmt->execute();
        
        $success = "注册码生成成功！";
    } catch (Exception $e) {
        $error = "生成失败: " . $e->getMessage();
    }
}

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50]) ? (int)$_GET['per_page'] : 20;

// 计算偏移量
$offset = ($page - 1) * $records_per_page;

// 获取总记录数
$count_query = "SELECT COUNT(*) as total FROM registration_codes";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 计算总页数
$total_pages = ceil($total_records / $records_per_page);
$page = min(max(1, $page), $total_pages); // 确保页码在有效范围内

// 修改查询语句,添加分页
$query = "SELECT rc.*,
          creator.username as created_by_name,
          used_person.username as used_by_name,
          s.company_name
          FROM registration_codes rc
          JOIN persons creator ON rc.created_by = creator.id
          LEFT JOIN persons used_person ON rc.used_by = used_person.id
          LEFT JOIN suppliers s ON rc.used_by = s.person_id
          ORDER BY rc.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>供应商注册码管理</h3>
                    <form method="post" class="d-inline">
                        <button type="submit" class="btn btn-primary">生成新注册码</button>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>注册码</th>
                                <th>状态</th>
                                <th>使用者</th>
                                <th>公司名称</th>
                                <th>创建者</th>
                                <th>创建时间</th>
                                <th>使用时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($codes as $code): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($code['code']); ?></td>
                                <td><?php echo $code['used'] ? '已使用' : '未使用'; ?></td>
                                <td><?php echo htmlspecialchars($code['used_by_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($code['company_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($code['created_by_name']); ?></td>
                                <td><?php echo htmlspecialchars($code['created_at']); ?></td>
                                <td><?php echo $code['used_at'] ? htmlspecialchars($code['used_at']) : ''; ?></td>
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
                        <nav aria-label="注册码列表分页">
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
    </div>
</div>

<script>
function changePerPage(value) {
    window.location.href = '?page=1&per_page=' + value;
}
</script>

<?php include '../includes/footer.php'; ?> 