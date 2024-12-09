<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // 从persons表查询用户
        $query = "SELECT p.*, 
                  CASE 
                    WHEN e.person_id IS NOT NULL THEN 'employee'
                    WHEN s.person_id IS NOT NULL THEN 'supplier'
                    WHEN c.person_id IS NOT NULL THEN 'customer'
                  END as user_type
                  FROM persons p
                  LEFT JOIN employees e ON p.id = e.person_id
                  LEFT JOIN suppliers s ON p.id = s.person_id
                  LEFT JOIN customers c ON p.id = c.person_id
                  WHERE p.username = :username";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->execute();
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($_POST['password'], $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_type'] = $user['user_type'];
                
                header("Location: /index.php");
                exit();
            }
        }
        
        $error = "用户名或密码错误";
    } catch (PDOException $e) {
        $error = "登录失败: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">登录</h3>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">登录</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 