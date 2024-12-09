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

// 获取注册码列表
$query = "SELECT rc.*,
          creator.username as created_by_name,
          used_person.username as used_by_name,
          s.company_name
          FROM registration_codes rc
          JOIN persons creator ON rc.created_by = creator.id
          LEFT JOIN persons used_person ON rc.used_by = used_person.id
          LEFT JOIN suppliers s ON rc.used_by = s.person_id
          ORDER BY rc.created_at DESC";
$stmt = $db->prepare($query);
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
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 