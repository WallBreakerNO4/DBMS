<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 获取用户信息
$query = "SELECT p.*, 
          CASE 
            WHEN e.person_id IS NOT NULL THEN 'employee'
            WHEN s.person_id IS NOT NULL THEN 'supplier'
            WHEN c.person_id IS NOT NULL THEN 'customer'
            ELSE 'admin'
          END as user_type,
          e.employee_number,
          e.department,
          e.position,
          s.company_name,
          s.contact_name,
          s.phone as supplier_phone,
          s.address as supplier_address,
          c.membership_level,
          c.points,
          c.phone as customer_phone,
          c.address as customer_address
          FROM persons p
          LEFT JOIN employees e ON p.id = e.person_id
          LEFT JOIN suppliers s ON p.id = s.person_id
          LEFT JOIN customers c ON p.id = c.person_id
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>个人资料</h3>
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
                    
                    <?php if ($user['user_type'] == 'employee'): ?>
                    <div class="mb-3">
                        <label class="form-label">员工编号</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['employee_number']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">部门</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['department']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">职位</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['position']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($user['user_type'] == 'supplier'): ?>
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
                        <p class="form-control-static"><?php echo htmlspecialchars($user['supplier_phone']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">地址</label>
                        <p class="form-control-static"><?php echo htmlspecialchars($user['supplier_address']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2">
                        <a href="change_password.php" class="btn btn-primary">修改密码</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 