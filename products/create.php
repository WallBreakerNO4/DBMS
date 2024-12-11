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

// 获取所有类别
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $query = "INSERT INTO products (name, category_id, description, price, stock_quantity, supplier_id) 
                  VALUES (:name, :category_id, :description, :price, :stock_quantity, :supplier_id)";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':category_id', $_POST['category_id']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':price', $_POST['price']);
        $stmt->bindParam(':stock_quantity', $_POST['stock_quantity']);
        
        // 如果是供应商，使用当前用户ID作为supplier_id
        if ($_SESSION['role'] === 'supplier') {
            $supplier_id = $_SESSION['user_id'];
        } else {
            $supplier_id = $_POST['supplier_id'];
        }
        $stmt->bindParam(':supplier_id', $supplier_id);
        
        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        }
    } catch (PDOException $e) {
        $error = "添加商品失败: " . $e->getMessage();
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>添加新商品</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">商品名称</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">商品类别</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">请选择类别</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">商品描述</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">价格</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>

                        <div class="mb-3">
                            <label for="stock_quantity" class="form-label">库存数量</label>
                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                        </div>

                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <div class="mb-3">
                            <label for="supplier_id" class="form-label">供应商</label>
                            <select class="form-control select2-suppliers" id="supplier_id" name="supplier_id" required>
                                <option value="">请选择供应商</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">添加商品</button>
                            <a href="index.php" class="btn btn-secondary">返回</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- 添加 Select2 相关资源 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2-suppliers').select2({
        placeholder: '请输入供应商名称搜索',
        minimumInputLength: 2,
        ajax: {
            url: '/api/suppliers/search.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.suppliers.map(function(supplier) {
                        return {
                            id: supplier.id,
                            text: supplier.company_name
                        };
                    }),
                    pagination: {
                        more: data.has_more
                    }
                };
            },
            cache: true
        }
    });
});
</script> 