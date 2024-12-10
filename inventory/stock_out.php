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

// 获取所有商品
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.stock_quantity > 0";

// 如果是供应商,只显示其自己的商品
if ($_SESSION['role'] === 'supplier') {
    $query .= " AND p.supplier_id = :supplier_id";
}

$query .= " ORDER BY p.name";
$stmt = $db->prepare($query);

// 如果是供应商,绑定supplier_id参数
if ($_SESSION['role'] === 'supplier') {
    $stmt->bindParam(':supplier_id', $_SESSION['user_id']);
}

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();

        // 获取当前库存
        $query = "SELECT stock_quantity FROM products WHERE id = :id FOR UPDATE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_POST['product_id']);
        $stmt->execute();
        $current_stock = $stmt->fetch(PDO::FETCH_ASSOC)['stock_quantity'];

        // 检查库存是否足够
        if ($current_stock < $_POST['quantity']) {
            throw new Exception("库存不足");
        }

        // 计算新库存
        $new_stock = $current_stock - $_POST['quantity'];

        // 更新商品库存
        $query = "UPDATE products 
                 SET stock_quantity = :quantity,
                     updated_at = CURRENT_TIMESTAMP 
                 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':quantity', $new_stock);
        $stmt->bindParam(':id', $_POST['product_id']);
        $stmt->execute();

        // 记录库存变动
        $query = "INSERT INTO inventory_records 
                  (product_id, type, quantity, before_quantity, after_quantity, operator_id, remark) 
                  VALUES (:product_id, '出库', :quantity, :before_quantity, :after_quantity, :operator_id, :remark)";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':product_id', $_POST['product_id']);
        $stmt->bindParam(':quantity', $_POST['quantity']);
        $stmt->bindParam(':before_quantity', $current_stock);
        $stmt->bindParam(':after_quantity', $new_stock);
        $stmt->bindParam(':operator_id', $_SESSION['user_id']); // 使用persons表的ID
        $stmt->bindParam(':remark', $_POST['remark']);
        
        $stmt->execute();
        
        $db->commit();
        $success = "出库成功！";
    } catch (Exception $e) {
        $db->rollBack();
        $error = "出库失败: " . $e->getMessage();
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>商品出库</h3>
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
                            <label for="product_id" class="form-label">选择商品</label>
                            <select class="form-control" id="product_id" name="product_id" required>
                                <option value="">请选择商品</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> 
                                        (<?php echo htmlspecialchars($product['category_name']); ?>) - 
                                        当前库存: <?php echo $product['stock_quantity']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="quantity" class="form-label">出库数量</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="remark" class="form-label">备注</label>
                            <textarea class="form-control" id="remark" name="remark" rows="3"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">确认出库</button>
                            <a href="index.php" class="btn btn-secondary">返回</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 