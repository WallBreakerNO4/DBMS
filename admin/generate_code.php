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

// 处理生成注册码请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // 生成随机注册码
        $code = bin2hex(random_bytes(16));
        
        // 保存注册码
        $query = "INSERT INTO registration_codes (code, created_by) VALUES (:code, :created_by)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "注册码生成成功：" . $code;
        }
    } catch (Exception $e) {
        $error = "生成注册码失败: " . $e->getMessage();
    }
}

// 获取所有注册码
$query = "SELECT rc.*, u.username as used_by_name, c.username as created_by_name 
          FROM registration_codes rc 
          LEFT JOIN users u ON rc.used_by = u.id 
          LEFT JOIN users c ON rc.created_by = c.id 
          ORDER BY rc.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h3>供应商注册码管理</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" class="mb-4">
                        <button type="submit" class="btn btn-primary">生成新注册码</button>
                    </form>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>注册码</th>
                                <th>状态</th>
                                <th>创建人</th>
                                <th>创建时间</th>
                                <th>使用者</th>
                                <th>使用时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($codes as $code): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($code['code']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $code['used'] ? 'secondary' : 'success'; ?>">
                                        <?php echo $code['used'] ? '已使用' : '未使用'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($code['created_by_name']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($code['created_at'])); ?></td>
                                <td><?php echo $code['used'] ? htmlspecialchars($code['used_by_name']) : '-'; ?></td>
                                <td><?php echo $code['used_at'] ? date('Y-m-d H:i:s', strtotime($code['used_at'])) : '-'; ?></td>
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