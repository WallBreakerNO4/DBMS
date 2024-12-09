<?php
session_start();
require_once '../config/database.php';

// 检查是否有注册码
if (!isset($_GET['code'])) {
    header("Location: /auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// 验证注册码
$query = "SELECT * FROM registration_codes 
          WHERE code = :code AND used = 0";
$stmt = $db->prepare($query);
$stmt->bindParam(':code', $_GET['code']);
$stmt->execute();

if (!$stmt->fetch()) {
    header("Location: /auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();
        
        // 在事务内重新验证注册码
        $query = "SELECT * FROM registration_codes 
                  WHERE code = :code AND used = 0 
                  FOR UPDATE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $_GET['code']);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            throw new Exception("无效的注册码");
        }
        
        // 检查用户名是否已存在
        $query = "SELECT id FROM persons WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            throw new Exception("用户名已存在");
        }
        
        // 创建用户账号
        $query = "INSERT INTO persons (username, password, email, role) 
                 VALUES (:username, :password, :email, 'supplier')";
        $stmt = $db->prepare($query);
        
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->execute();
        
        $person_id = $db->lastInsertId();
        
        // 创建供应商信息
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
        
        // 更新注册码状态
        $query = "UPDATE registration_codes 
                 SET used = 1, 
                     used_by = :used_by,
                     used_at = CURRENT_TIMESTAMP 
                 WHERE code = :code";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':used_by', $person_id);
        $stmt->bindParam(':code', $_GET['code']);
        $stmt->execute();
        
        $db->commit();
        header("Location: /auth/login.php?registered=1");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = "注册失败: " . $e->getMessage();
    }
}
?> 