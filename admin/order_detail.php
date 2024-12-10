<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是管理员或员工
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'employee'])) {
    http_response_code(404);
    include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    exit();
}

// 检查是否有订单ID
if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 获取订单基本信息
    $query = "SELECT o.*, 
              DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i:%s') as order_time,
              DATE_FORMAT(o.updated_at, '%Y-%m-%d %H:%i:%s') as update_time,
              c.username as customer_name,
              c.email as customer_email,
              cu.phone, cu.address
              FROM orders o
              LEFT JOIN persons c ON o.customer_id = c.id
              LEFT JOIN customers cu ON o.customer_id = cu.person_id
              WHERE o.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('订单不存在');
    }
    
    // 获取订单商品详情
    $query = "SELECT oi.*, p.name as product_name,
              s.company_name as supplier_name
              FROM order_items oi
              LEFT JOIN products p ON oi.product_id = p.id
              LEFT JOIN suppliers s ON p.supplier_id = s.person_id
              WHERE oi.order_id = :order_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $_GET['id']);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>订单详情</h2>
        <a href="orders.php" class="btn btn-secondary">返回订单列表</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <!-- 订单状态和操作 -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>订单状态</h5>
                        <span class="badge bg-<?php 
                            echo match($order['status']) {
                                '待付款' => 'warning',
                                '已付款' => 'info',
                                '已发货' => 'primary',
                                '已完成' => 'success',
                                '已取消' => 'danger',
                                default => 'secondary'
                            };
                        ?> fs-6">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if ($order['status'] === '已付款'): ?>
                            <button type="button" 
                                    class="btn btn-success"
                                    onclick="updateOrderStatus(<?php echo $order['id']; ?>, '已发货')">
                                发货
                            </button>
                        <?php endif; ?>
                        <?php if ($order['status'] === '已发货'): ?>
                            <button type="button" 
                                    class="btn btn-success"
                                    onclick="updateOrderStatus(<?php echo $order['id']; ?>, '已完成')">
                                完成订单
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 订单信息 -->
        <div class="row">
            <!-- 客户信息 -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">客户信息</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 120px;">客户名称：</th>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <th>联系电话：</th>
                                <td><?php echo htmlspecialchars($order['phone'] ?? '无'); ?></td>
                            </tr>
                            <tr>
                                <th>邮箱：</th>
                                <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                            </tr>
                            <tr>
                                <th>收货地址：</th>
                                <td><?php echo htmlspecialchars($order['address'] ?? '无'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 订单信息 -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">订单信息</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 120px;">订单编号：</th>
                                <td><?php echo $order['id']; ?></td>
                            </tr>
                            <tr>
                                <th>下单时间：</th>
                                <td><?php echo $order['order_time']; ?></td>
                            </tr>
                            <tr>
                                <th>最后更新：</th>
                                <td><?php echo $order['update_time']; ?></td>
                            </tr>
                            <tr>
                                <th>订单金额：</th>
                                <td>￥<?php echo number_format($order['total_amount'], 2); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 商品清单 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">商品清单</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>商品名称</th>
                                <th>供应商</th>
                                <th>单价</th>
                                <th>数量</th>
                                <th>小计</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['supplier_name']); ?></td>
                                <td>￥<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>￥<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>总计：</strong></td>
                                <td><strong>￥<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateOrderStatus(orderId, status) {
    if (confirm(`确定要将订单状态更新为"${status}"吗？`)) {
        fetch('/admin/update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || '更新失败，请重试');
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?> 