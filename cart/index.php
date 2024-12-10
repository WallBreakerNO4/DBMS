<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是顾客
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: /auth/login.php");
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 获取购物车商品
$query = "SELECT ci.*, p.name, p.price, p.stock_quantity, c.name as category_name
          FROM cart_items ci
          JOIN products p ON ci.product_id = p.id
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE ci.customer_id = :customer_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':customer_id', $_SESSION['user_id']);
$stmt->execute();
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 计算总金额
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}
?>

<div class="container">
    <h2 class="mb-4">我的购物车</h2>
    
    <?php if (empty($cart_items)): ?>
    <div class="alert alert-info">
        购物车是空的，<a href="/products/display.php">去购物</a>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>商品名称</th>
                            <th>类别</th>
                            <th>单价</th>
                            <th>数量</th>
                            <th>小计</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td>￥<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <input type="number" 
                                       class="form-control form-control-sm" 
                                       style="width: 80px;"
                                       value="<?php echo $item['quantity']; ?>"
                                       min="1"
                                       max="<?php echo $item['stock_quantity']; ?>"
                                       onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                            </td>
                            <td>￥<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <button class="btn btn-danger btn-sm" 
                                        onclick="removeItem(<?php echo $item['id']; ?>)">
                                    删除
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end"><strong>总计：</strong></td>
                            <td colspan="2"><strong>￥<?php echo number_format($total_amount, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary" onclick="checkout()">结算</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(itemId, quantity) {
    fetch('/cart/update_quantity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '更新失败，请重试');
            location.reload();
        }
    });
}

function removeItem(itemId) {
    if (confirm('确定要删除这个商品吗？')) {
        fetch('/cart/remove_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                item_id: itemId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || '删除失败，请重试');
            }
        });
    }
}

function checkout() {
    if (confirm('确定要结算购物车吗？')) {
        fetch('/cart/checkout.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('订单创建成功！');
                location.href = '/orders/detail.php?id=' + data.order_id;
            } else {
                alert(data.message || '结算失败，请重试');
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?> 