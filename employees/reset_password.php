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

// 获取员工信息
if (isset($_GET['id'])) {
    $query = "SELECT p.*, e.position
              FROM persons p
              JOIN employees e ON p.id = e.person_id
              WHERE p.id = :id AND p.role = 'employee'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        header("Location: index.php");
        exit();
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // 验证新密码
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            throw new Exception("两次输入的密码不一致");
        }

        if (strlen($_POST['new_password']) < 6) {
            throw new Exception("密码长度不能少于6个字符");
        }

        // 更新密码
        $password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $query = "UPDATE persons 
                 SET password = :password,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();

        $success = "密码重置成功！";
    } catch (Exception $e) {
        $error = "重置失败: " . $e->getMessage();
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>重置员工密码</h3>
                    <p class="mb-0">员工：<?php echo htmlspecialchars($employee['username']); ?> 
                       (<?php echo htmlspecialchars($employee['position']); ?>)</p>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateForm()">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">新密码</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" required>
                            <div class="form-text">密码长度至少6个字符</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">确认新密码</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">重置密码</button>
                            <a href="index.php" class="btn btn-secondary">返回</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validateForm() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (newPassword.length < 6) {
        alert('新密码长度不能少于6个字符');
        return false;
    }

    if (newPassword !== confirmPassword) {
        alert('两次输入的密码不一致');
        return false;
    }

    return true;
}
</script>

<?php include '../includes/footer.php'; ?> 