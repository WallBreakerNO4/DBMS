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

// 获取供应商列表
$query = "SELECT p.*, s.*
          FROM persons p
          JOIN suppliers s ON p.id = s.person_id
          WHERE p.role = 'supplier'
          ORDER BY s.company_name";
$stmt = $db->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>供应商管理</h2>
        <a href="/admin/generate_code.php" class="btn btn-primary">生成注册码</a>
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
                        <th>公司名称</th>
                        <th>联系人</th>
                        <th>邮箱</th>
                        <th>电话</th>
                        <th>地址</th>
                        <th>注册时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($supplier['id']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['username']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['contact_name']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['address']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['created_at']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo $supplier['id']; ?>" 
                                   class="btn btn-sm btn-primary">编辑</a>
                                <a href="reset_password.php?id=<?php echo $supplier['id']; ?>" 
                                   class="btn btn-sm btn-warning">重置密码</a>
                                <a href="delete.php?id=<?php echo $supplier['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('确定要删除该供应商吗？删除后将无法恢复！')">删除</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>