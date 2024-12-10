<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是顾客
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(404);
    include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // 更新 persons 表中的邮箱
        $query = "UPDATE persons SET 
                  email = :email
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();

        // 检查是否已有 customers 记录
        $query = "SELECT person_id FROM customers WHERE person_id = :person_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':person_id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // 更新现有记录
            $query = "UPDATE customers SET 
                      phone = :phone,
                      address = :address
                      WHERE person_id = :person_id";
        } else {
            // 插入新记录
            $query = "INSERT INTO customers (person_id, phone, address) 
                      VALUES (:person_id, :phone, :address)";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':phone', $_POST['phone']);
        $stmt->bindParam(':address', $_POST['address']);
        $stmt->bindParam(':person_id', $_SESSION['user_id']);
        $stmt->execute();

        $db->commit();
        $success = '个人资料更新成功！';
    } catch (Exception $e) {
        $db->rollBack();
        $error = '更新失败：' . $e->getMessage();
    }
}

// 获取用户信息
$query = "SELECT p.*, c.phone, c.address 
          FROM persons p 
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
                    <h3>编辑个人资料</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">邮箱</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">联系电话</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">地址</label>
                            <textarea class="form-control" id="address" name="address" rows="3"
                                    ><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">保存修改</button>
                            <a href="/auth/customer_profile.php" class="btn btn-secondary">返回</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 