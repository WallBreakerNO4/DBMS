<?php
session_start();
require_once '../config/database.php';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // 验证注册码
        $query = "SELECT id FROM registration_codes 
                  WHERE code = :code AND used = FALSE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $_POST['registration_code']);
        $stmt->execute();
        
        if (!$code = $stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("无效的注册码");
        }
        
        // 检查用户名是否已存在
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->execute();
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("用户名已存在");
        }
        
        // 创建用户
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, password, email, role) 
                  VALUES (:username, :password, :email, 'supplier')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->execute();
        
        $user_id = $db->lastInsertId();
        
        // 标记注册码为已使用
        $query = "UPDATE registration_codes 
                  SET used = TRUE, used_by = :user_id, used_at = CURRENT_TIMESTAMP 
                  WHERE id = :code_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':code_id', $code['id']);
        $stmt->execute();
        
        $db->commit();
        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>供应商注册</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateForm()">
                        <div class="mb-3">
                            <label for="registration_code" class="form-label">注册码</label>
                            <input type="text" class="form-control" id="registration_code" 
                                   name="registration_code" required>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">用户名</label>
                            <input type="text" class="form-control" id="username" 
                                   name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">电子邮箱</label>
                            <input type="email" class="form-control" id="email" 
                                   name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">密码</label>
                            <input type="password" class="form-control" id="password" 
                                   name="password" required>
                            <div class="form-text">密码长度至少6个字符</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">确认密码</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">注册</button>
                            <a href="login.php" class="btn btn-secondary">返回登录</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validateForm() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (password.length < 6) {
        alert('密码长度不能少于6个字符');
        return false;
    }

    if (password !== confirmPassword) {
        alert('两次输入的密码不一致');
        return false;
    }

    return true;
}
</script>

<?php include '../includes/footer.php'; ?> 