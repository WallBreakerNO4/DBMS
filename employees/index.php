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

// 获取员工列表
$query = "SELECT p.*, e.*, d.name as department_name
          FROM persons p
          JOIN employees e ON p.id = e.person_id
          LEFT JOIN departments d ON e.department_id = d.id
          WHERE p.role = 'employee'
          ORDER BY p.username";
$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>员工管理</h2>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>邮箱</th>
                        <th>部门</th>
                        <th>职位</th>
                        <th>电话</th>
                        <th>地址</th>
                        <th>注册时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employee['id']); ?></td>
                        <td><?php echo htmlspecialchars($employee['username']); ?></td>
                        <td><?php echo htmlspecialchars($employee['email']); ?></td>
                        <td><?php echo htmlspecialchars($employee['department_name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                        <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                        <td><?php echo htmlspecialchars($employee['address']); ?></td>
                        <td><?php echo htmlspecialchars($employee['created_at']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo $employee['id']; ?>" 
                                   class="btn btn-sm btn-primary">编辑</a>
                                <a href="reset_password.php?id=<?php echo $employee['id']; ?>" 
                                   class="btn btn-sm btn-warning">重置密码</a>
                                <a href="delete.php?id=<?php echo $employee['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('确定要删除该员工吗？删除后将无法恢复！')">删除</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 