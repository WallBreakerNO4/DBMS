<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是员工
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    http_response_code(404);
    include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 获取员工信息
$query = "SELECT p.*, e.*, d.name as department_name
          FROM persons p
          JOIN employees e ON p.id = e.person_id
          LEFT JOIN departments d ON e.department_id = d.id
          WHERE p.id = :id AND p.role = 'employee'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: /index.php");
    exit();
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>员工资料</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">用户名</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">邮箱</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">所属部门</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['department_name']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">职位</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['position']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">联系电话</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['phone']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">地址</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['address']); ?></p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="/auth/edit_employee_profile.php" class="btn btn-primary">编辑资料</a>
                        <a href="change_password.php" class="btn btn-primary">修改密码</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 