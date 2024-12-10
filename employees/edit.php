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

// 获取部门列表
$query = "SELECT * FROM departments ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取员工信息
if (isset($_GET['id'])) {
    $query = "SELECT p.*, e.*
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
        $db->beginTransaction();
        
        // 检��新用户名是否已存在（如果用户名被修改了）
        if ($_POST['username'] !== $employee['username']) {
            $query = "SELECT id FROM persons WHERE username = :username AND id != :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $_POST['username']);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                throw new Exception("该用户名已被使用");
            }
        }
        
        // 更新persons表
        $query = "UPDATE persons 
                 SET username = :username,
                     email = :email,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        // 更新employees表
        $query = "UPDATE employees 
                 SET position = :position,
                     department_id = :department_id,
                     phone = :phone,
                     address = :address
                 WHERE person_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':position', $_POST['position']);
        $stmt->bindParam(':department_id', $_POST['department_id']);
        $stmt->bindParam(':phone', $_POST['phone']);
        $stmt->bindParam(':address', $_POST['address']);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        $db->commit();
        $success = "更新成功！";
    } catch (Exception $e) {
        $db->rollBack();
        $error = "更新失败: " . $e->getMessage();
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>编辑员工资料</h3>
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
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?php echo htmlspecialchars($employee['username']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">电子邮箱</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="department_id" class="form-label">所属部门</label>
                            <select class="form-control" id="department_id" name="department_id" required>
                                <option value="">请选择部门</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department['id']; ?>"
                                            <?php echo $department['id'] == $employee['department_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($department['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="position" class="form-label">职位</label>
                            <input type="text" class="form-control" id="position" name="position" 
                                   value="<?php echo htmlspecialchars($employee['position']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">联系电话</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($employee['phone']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">地址</label>
                            <textarea class="form-control" id="address" name="address" 
                                      rows="3" required><?php echo htmlspecialchars($employee['address']); ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">保存修改</button>
                            <a href="index.php" class="btn btn-secondary">返回</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 