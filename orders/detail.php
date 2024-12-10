<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是顾客
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: /auth/login.php");
    exit();
}

// 检查是否有订单ID参数
if (!isset($_GET['id'])) {
    header("Location: /orders");
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 获取订单信息
    $query = "SELECT o.*, 
              DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i:%s') as order_time,
              c.phone, c.address
              FROM orders o
              LEFT JOIN customers c ON o.customer_id = c.person_id
              WHERE o.id = :id AND o.customer_id = :customer_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('订单不存在');
    }
    
    // 获取订单商品
    $query = "SELECT oi.*, p.name as product_name, c.name as category_name
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE oi.order_id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $_GET['id']);
    $stmt->execute();
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>订单详情</h2>
        <a href="/orders" class="btn btn-secondary">返回订单列表</a>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php else: ?>
    
    <!-- 订单基本信息 -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">订单信息</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>订单编号：</strong><?php echo $order['id']; ?></p>
                    <p><strong>下单时间：</strong><?php echo $order['order_time']; ?></p>
                    <p><strong>订单状态：</strong>
                        <span class="badge bg-<?php 
                            echo match($order['status']) {
                                '待付款' => 'warning',
                                '已付款' => 'info',
                                '已发货' => 'primary',
                                '已完成' => 'success',
                                '已取消' => 'danger',
                                default => 'secondary'
                            };
                        ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>收货电话：</strong><?php echo htmlspecialchars($order['phone'] ?? '未设置'); ?></p>
                    <p><strong>收货地址：</strong><?php echo htmlspecialchars($order['address'] ?? '未设置'); ?></p>
                    <p><strong>订单金额：</strong>￥<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 订单商品列表 -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">商品清单</h5>
        </div>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td>￥<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>￥<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end"><strong>总计：</strong></td>
                            <td><strong>￥<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php if ($order['status'] === '待付款'): ?>
        <div class="card-footer text-end">
            <button class="btn btn-danger me-2" onclick="cancelOrder(<?php echo $order['id']; ?>)">取消订单</button>
            <button class="btn btn-primary" onclick="payOrder(<?php echo $order['id']; ?>)">立即付款</button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('确定要取消这个订单吗？')) {
        fetch('/orders/cancel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || '取消失败，请重试');
            }
        });
    }
}

function payOrder(orderId) {
    if (confirm('确定要支付这个订单吗？')) {
        fetch('/orders/pay.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('支付成功！');
                location.reload();
            } else {
                alert(data.message || '支付失败，请重试');
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?> 