<?php
session_start();
require_once '../config/database.php';
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 获取部门列表 (移到这里，确保页面加载时就能获取到部门列表)
$departments = [];
$query = "SELECT id, name FROM departments ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();
        
        // 如果是供应商注册,验证注册码
        if ($_POST['role'] === 'supplier') {
            $query = "SELECT id FROM registration_codes 
                      WHERE code = :code AND used = FALSE";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':code', $_POST['registration_code']);
            $stmt->execute();
            
            if (!$code = $stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception("无效的注册码");
            }
        }
        
        // 检查用户名是否已存在
        $query = "SELECT id FROM persons WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->execute();
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("用户名已存在");
        }
        
        // 创建person记录
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query = "INSERT INTO persons (username, password, email, role) 
                  VALUES (:username, :password, :email, :role)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':role', $_POST['role']);
        $stmt->execute();
        
        $person_id = $db->lastInsertId();
        
        // 根据不同角色创建对应的记录
        switch ($_POST['role']) {
            case 'supplier':
                $query = "INSERT INTO suppliers 
                          (person_id, company_name, contact_name, phone, address) 
                          VALUES (:person_id, :company_name, :contact_name, :phone, :address)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':person_id', $person_id);
                $stmt->bindParam(':company_name', $_POST['company_name']);
                $stmt->bindParam(':contact_name', $_POST['contact_name']);
                $stmt->bindParam(':phone', $_POST['phone']);
                $stmt->bindParam(':address', $_POST['address']);
                $stmt->execute();
                
                // 标记注册码为已使用
                $query = "UPDATE registration_codes 
                          SET used = TRUE, used_by = :person_id, used_at = CURRENT_TIMESTAMP 
                          WHERE id = :code_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':person_id', $person_id);
                $stmt->bindParam(':code_id', $code['id']);
                $stmt->execute();
                break;
                
            case 'customer':
                $query = "INSERT INTO customers 
                          (person_id, phone, address) 
                          VALUES (:person_id, :phone, :address)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':person_id', $person_id);
                $stmt->bindParam(':phone', $_POST['phone']);
                $stmt->bindParam(':address', $_POST['address']);
                $stmt->execute();
                break;
                
            case 'employee':
                $query = "INSERT INTO employees 
                          (person_id, phone, position, department_id, address) 
                          VALUES (:person_id, :phone, :position, :department_id, :address)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':person_id', $person_id);
                $stmt->bindParam(':phone', $_POST['phone']);
                $stmt->bindParam(':position', $_POST['position']);
                $stmt->bindParam(':department_id', $_POST['department_id']);
                $stmt->bindParam(':address', $_POST['address']);
                $stmt->execute();
                break;
        }
        
        $db->commit();
        
        // 注册成功后直接登录
        $_SESSION['user_id'] = $person_id;
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['role'] = $_POST['role'];
        $_SESSION['user_type'] = $_POST['role'];
        
        header("Location: /index.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = "注册失败: " . $e->getMessage();
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>用户注册</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateForm()">
                        <div class="mb-3">
                            <label for="role" class="form-label">注册身份</label>
                            <select class="form-control" id="role" name="role" onchange="toggleFields()">
                                <option value="customer">消费者</option>
                                <option value="supplier">供应商</option>
                                <option value="employee">员工</option>
                            </select>
                        </div>

                        <!-- 供应商注册码 -->
                        <div id="supplier_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="registration_code" class="form-label">注册码</label>
                                <input type="text" class="form-control" id="registration_code" name="registration_code">
                            </div>
                            <div class="mb-3">
                                <label for="company_name" class="form-label">公司名称</label>
                                <input type="text" class="form-control" id="company_name" name="company_name">
                            </div>
                            <div class="mb-3">
                                <label for="contact_name" class="form-label">联系人</label>
                                <input type="text" class="form-control" id="contact_name" name="contact_name">
                            </div>
                        </div>

                        <!-- 员工特有字段 -->
                        <div id="employee_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">所属部门</label>
                                <select class="form-control" id="department_id" name="department_id">
                                    <option value="">请选择部门</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['id']; ?>">
                                            <?php echo htmlspecialchars($department['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="position" class="form-label">职位</label>
                                <input type="text" class="form-control" id="position" name="position">
                            </div>
                        </div>

                        <!-- 通用字段 -->
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">电子邮箱</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">密码</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">密码长度至少6个字符</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">确认密码</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <!-- 共用字段 -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">联系电话</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">地址</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
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
function toggleFields() {
    const role = document.getElementById('role').value;
    const supplierFields = document.getElementById('supplier_fields');
    const employeeFields = document.getElementById('employee_fields');
    
    // 隐藏所有特殊字段
    supplierFields.style.display = 'none';
    employeeFields.style.display = 'none';
    
    // 根据选择显示对应字段
    if (role === 'supplier') {
        supplierFields.style.display = 'block';
    } else if (role === 'employee') {
        employeeFields.style.display = 'block';
    }
    
    // 更新必填字段
    updateRequiredFields(role);
}

function updateRequiredFields(role) {
    const registrationCode = document.getElementById('registration_code');
    const companyName = document.getElementById('company_name');
    const contactName = document.getElementById('contact_name');
    const position = document.getElementById('position');
    const departmentId = document.getElementById('department_id');
    
    // 重置所有字段的required属性
    registrationCode.required = false;
    companyName.required = false;
    contactName.required = false;
    position.required = false;
    departmentId.required = false;
    
    // 根据角色设置必填字段
    if (role === 'supplier') {
        registrationCode.required = true;
        companyName.required = true;
        contactName.required = true;
    } else if (role === 'employee') {
        position.required = true;
        departmentId.required = true;
    }
}

function validateForm() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const role = document.getElementById('role').value;

    if (password.length < 6) {
        alert('密码长度不能少于6个字符');
        return false;
    }

    if (password !== confirmPassword) {
        alert('两次输入的密码不一致');
        return false;
    }

    // 供应商注册时验证必填字段
    if (role === 'supplier') {
        const registrationCode = document.getElementById('registration_code').value;
        const companyName = document.getElementById('company_name').value;
        const contactName = document.getElementById('contact_name').value;
        
        if (!registrationCode || !companyName || !contactName) {
            alert('请填写所有必填字段');
            return false;
        }
    }
    
    // 员工注册时验证必填字段
    if (role === 'employee') {
        const position = document.getElementById('position').value;
        const departmentId = document.getElementById('department_id').value;
        
        if (!position || !departmentId) {
            alert('请填写所有必填字段');
            return false;
        }
    }

    return true;
}

// 页面加载时初始化字段显示
document.addEventListener('DOMContentLoaded', function() {
    toggleFields();
});
</script>

<?php include '../includes/footer.php'; ?> 