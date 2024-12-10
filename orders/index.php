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

// 获取筛选参数
$status = isset($_GET['status']) ? $_GET['status'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// 构建查询
$query = "SELECT o.*, 
          DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i:%s') as order_time,
          COUNT(oi.id) as item_count,
          GROUP_CONCAT(p.name SEPARATOR '、') as product_names
          FROM orders o
          LEFT JOIN order_items oi ON o.id = oi.order_id
          LEFT JOIN products p ON oi.product_id = p.id
          WHERE o.customer_id = :customer_id";

// 添加筛选条件
if (!empty($status)) {
    $query .= " AND o.status = :status";
}
if (!empty($start_date)) {
    $query .= " AND DATE(o.created_at) >= :start_date";
}
if (!empty($end_date)) {
    $query .= " AND DATE(o.created_at) <= :end_date";
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':customer_id', $_SESSION['user_id']);

if (!empty($status)) {
    $stmt->bindParam(':status', $status);
}
if (!empty($start_date)) {
    $stmt->bindParam(':start_date', $start_date);
}
if (!empty($end_date)) {
    $stmt->bindParam(':end_date', $end_date);
}

$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>我的订单</h2>
    </div>

    <!-- 筛选表单 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">订单状态</label>
                    <select name="status" class="form-select">
                        <option value="">全部</option>
                        <option value="待付款" <?php echo $status === '待付款' ? 'selected' : ''; ?>>待付款</option>
                        <option value="已付款" <?php echo $status === '已付款' ? 'selected' : ''; ?>>已付款</option>
                        <option value="已发货" <?php echo $status === '已发货' ? 'selected' : ''; ?>>已发货</option>
                        <option value="已完成" <?php echo $status === '已完成' ? 'selected' : ''; ?>>已完成</option>
                        <option value="已取消" <?php echo $status === '已取消' ? 'selected' : ''; ?>>已取消</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">开始日期</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">结束日期</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">筛选</button>
                        <a href="index.php" class="btn btn-secondary">重置</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 订单列表 -->
    <?php if (empty($orders)): ?>
    <div class="alert alert-info">
        暂无订单记录
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>订单编号</th>
                            <th>商品</th>
                            <th>金额</th>
                            <th>状态</th>
                            <th>下单时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td>
                                <div class="text-truncate" style="max-width: 300px;" title="<?php echo htmlspecialchars($order['product_names']); ?>">
                                    <?php echo htmlspecialchars($order['product_names']); ?>
                                </div>
                                <small class="text-muted">共<?php echo $order['item_count']; ?>件商品</small>
                            </td>
                            <td>￥<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
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
                            </td>
                            <td><?php echo $order['order_time']; ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="detail.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        查看详情
                                    </a>
                                    <?php if ($order['status'] === '待付款'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success"
                                            onclick="payOrder(<?php echo $order['id']; ?>)">
                                        立即付款
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                        取消订单
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
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