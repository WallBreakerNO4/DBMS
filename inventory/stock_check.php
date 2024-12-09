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
          ORDER BY c.name, p.name";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();
        
        foreach ($_POST['actual_quantity'] as $product_id => $actual_quantity) {
            // 获取当前库存
            $query = "SELECT stock_quantity FROM products WHERE id = :id FOR UPDATE";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $product_id);
            $stmt->execute();
            $current_stock = $stmt->fetch(PDO::FETCH_ASSOC)['stock_quantity'];
            
            // 计算差异
            $difference = $actual_quantity - $current_stock;
            
            if ($difference != 0) {
                // 更新商品库存
                $query = "UPDATE products 
                         SET stock_quantity = :quantity,
                             updated_at = CURRENT_TIMESTAMP 
                         WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':quantity', $actual_quantity);
                $stmt->bindParam(':id', $product_id);
                $stmt->execute();
                
                // 记录库存变动
                $query = "INSERT INTO inventory_records 
                          (product_id, type, quantity, before_quantity, after_quantity, operator_id, remark) 
                          VALUES (:product_id, '盘点调整', :quantity, :before_quantity, :after_quantity, :operator_id, :remark)";
                $stmt = $db->prepare($query);
                
                $stmt->bindParam(':product_id', $product_id);
                $stmt->bindParam(':quantity', abs($difference));
                $stmt->bindParam(':before_quantity', $current_stock);
                $stmt->bindParam(':after_quantity', $actual_quantity);
                $stmt->bindParam(':operator_id', $_SESSION['user_id']); // 使用persons表的ID
                
                $remark = "库存盘点: " . ($difference > 0 ? "实际库存大于系统库存" : "实际库存小于系统库存");
                $stmt->bindParam(':remark', $remark);
                
                $stmt->execute();
            }
        }
        
        $db->commit();
        $success = "库存盘点完成！";
    } catch (Exception $e) {
        $db->rollBack();
        $error = "盘点失败: " . $e->getMessage();
    }
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>库存盘点</h2>
        <a href="index.php" class="btn btn-secondary">返回</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" id="stockCheckForm">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col">
                        <h5 class="mb-0">商品库存盘点表</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>商品名称</th>
                            <th>类别</th>
                            <th>系统库存</th>
                            <th>实际库存</th>
                            <th>差异</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td class="system-quantity">
                                <?php echo $product['stock_quantity']; ?>
                            </td>
                            <td>
                                <input type="number" 
                                       class="form-control actual-quantity" 
                                       name="actual_quantity[<?php echo $product['id']; ?>]" 
                                       value="<?php echo $product['stock_quantity']; ?>" 
                                       min="0" 
                                       required>
                            </td>
                            <td class="difference">0</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary" onclick="return confirm('确定要提交盘点结果吗？')">
                    提交盘点结果
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// 计算差异
document.querySelectorAll('.actual-quantity').forEach(input => {
    input.addEventListener('input', function() {
        const row = this.closest('tr');
        const systemQuantity = parseInt(row.querySelector('.system-quantity').textContent);
        const actualQuantity = parseInt(this.value) || 0;
        const difference = actualQuantity - systemQuantity;
        
        row.querySelector('.difference').textContent = difference;
        row.querySelector('.difference').style.color = 
            difference > 0 ? 'green' : (difference < 0 ? 'red' : 'black');
    });
});

// 表单验证
document.getElementById('stockCheckForm').addEventListener('submit', function(e) {
    const inputs = document.querySelectorAll('.actual-quantity');
    for (let input of inputs) {
        if (input.value < 0) {
            e.preventDefault();
            alert('实际库存不能为负数');
            input.focus();
            return;
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>