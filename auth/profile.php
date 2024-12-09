<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是供应商
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    http_response_code(404);
    include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 获取供应商信息
$query = "SELECT p.*, s.*
          FROM persons p
          JOIN suppliers s ON p.id = s.person_id
          WHERE p.id = :id AND p.role = 'supplier'";
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
                    <h3>供应商资料</h3>
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
                        <label class="form-label">公司名称</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['company_name']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">联系人</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['contact_name']); ?></p>
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
                        <a href="/auth/edit_profile.php" class="btn btn-primary">编辑资料</a>
                        <a href="change_password.php" class="btn btn-primary">修改密码</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 