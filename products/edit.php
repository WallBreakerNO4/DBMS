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

// 获取商品信息
if (isset($_GET['id'])) {
    $query = "SELECT * FROM products WHERE id = :id";
    
    // 如果是供应商，只能编辑自己的商品
    if ($_SESSION['role'] === 'supplier') {
        $query .= " AND supplier_id = :supplier_id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    
    // 如果是供应商，绑定supplier_id参数
    if ($_SESSION['role'] === 'supplier') {
        $stmt->bindParam(':supplier_id', $_SESSION['user_id']);
    }
    
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}

// 获取所有类别
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取供应商列表
$query = "SELECT p.id, s.company_name 
          FROM persons p 
          JOIN suppliers s ON p.id = s.person_id 
          WHERE p.role = 'supplier' 
          ORDER BY s.company_name";
$stmt = $db->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $query = "UPDATE products 
                  SET name = :name, 
                      category_id = :category_id, 
                      description = :description, 
                      price = :price, 
                      stock_quantity = :stock_quantity,
                      supplier_id = :supplier_id 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':category_id', $_POST['category_id']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':price', $_POST['price']);
        $stmt->bindParam(':stock_quantity', $_POST['stock_quantity']);
        $stmt->bindParam(':supplier_id', $_POST['supplier_id']);
        
        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        }
    } catch (PDOException $e) {
        $error = "更新商品失败: " . $e->getMessage();
    }
}

// 供应商只能修改库存数量，不能修改其他信息
if ($_SESSION['role'] === 'supplier') {
    // 处理表单提交
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $query = "UPDATE products 
                    SET stock_quantity = :stock_quantity 
                    WHERE id = :id AND supplier_id = :supplier_id";
            
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->bindParam(':stock_quantity', $_POST['stock_quantity']);
            $stmt->bindParam(':supplier_id', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                header("Location: index.php");
                exit();
            }
        } catch (PDOException $e) {
            $error = "更新库存失败: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>编辑商品</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <!-- 管理员可以看到所有字段 -->
                        <div class="mb-3">
                            <label for="name" class="form-label">商品名称</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">商品类别</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">请选择类别</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">商品描述</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                    ><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">价格</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" 
                                   value="<?php echo htmlspecialchars($product['price']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="stock_quantity" class="form-label">库存数量</label>
                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                   value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="supplier_id" class="form-label">供应商</label>
                            <select class="form-control" id="supplier_id" name="supplier_id" required>
                                <option value="">请选择供应商</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" 
                                            <?php echo $supplier['id'] == $product['supplier_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <!-- 供应商只能看到和修改库存数量 -->
                        <div class="mb-3">
                            <label for="name" class="form-label">商品名称</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="stock_quantity" class="form-label">库存数量</label>
                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                   value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
                        </div>
                        <?php endif; ?>

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