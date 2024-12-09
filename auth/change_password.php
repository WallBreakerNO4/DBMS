<?php
session_start();
require_once '../config/database.php';
include '../includes/header.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // 获取当前用户信息
        $query = "SELECT password FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 验证当前密码
        if (!password_verify($_POST['current_password'], $user['password'])) {
            throw new Exception("当前密码不正确");
        }

        // 验证新密码
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            throw new Exception("两次输入的新密码不一致");
        }

        // 验证新密码长度
        if (strlen($_POST['new_password']) < 6) {
            throw new Exception("新密码长度不能少于6个字符");
        }

        // 更新密码
        $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password', $new_password_hash);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "密码修改成功！";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>修改密码</h3>
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
                            <label for="current_password" class="form-label">当前密码</label>
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required>
                        </div>

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
                            <button type="submit" class="btn btn-primary">修改密码</button>
                            <a href="/index.php" class="btn btn-secondary">返回</a>
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
        alert('两次输入的新密码不一致');
        return false;
    }

    return true;
}
</script>

<?php include '../includes/footer.php'; ?> 