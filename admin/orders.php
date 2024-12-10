<?php
session_start();
require_once '../config/database.php';

// 检查用户是否登录且是管理员或员工
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'employee'])) {
    http_response_code(404);
    include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    exit();
}

include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 获取筛选参数
$status = isset($_GET['status']) ? $_GET['status'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50]) ? (int)$_GET['per_page'] : 20;

// 计算偏移量
$offset = ($page - 1) * $records_per_page;

// 构建查询
$query = "SELECT o.*, 
          DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i:%s') as order_time,
          c.username as customer_name,
          cu.phone, cu.address,
          COUNT(oi.id) as item_count,
          GROUP_CONCAT(p.name SEPARATOR '、') as product_names
          FROM orders o
          LEFT JOIN persons c ON o.customer_id = c.id
          LEFT JOIN customers cu ON o.customer_id = cu.person_id
          LEFT JOIN order_items oi ON o.id = oi.order_id
          LEFT JOIN products p ON oi.product_id = p.id
          WHERE 1=1";

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
if (!empty($search)) {
    $query .= " AND (o.id LIKE :search 
                OR c.username LIKE :search 
                OR cu.phone LIKE :search 
                OR p.name LIKE :search)";
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

// 获取总记录数
$count_query = "SELECT COUNT(DISTINCT o.id) as total 
                FROM orders o
                LEFT JOIN persons c ON o.customer_id = c.id
                LEFT JOIN customers cu ON o.customer_id = cu.person_id
                WHERE 1=1";

// 添加搜索条件到计数查询
if (!empty($status)) {
    $count_query .= " AND o.status = :status";
}
if (!empty($start_date)) {
    $count_query .= " AND DATE(o.created_at) >= :start_date";
}
if (!empty($end_date)) {
    $count_query .= " AND DATE(o.created_at) <= :end_date";
}
if (!empty($search)) {
    $count_query .= " AND (o.id LIKE :search 
                    OR c.username LIKE :search 
                    OR cu.phone LIKE :search)";
}

$count_stmt = $db->prepare($count_query);

// 绑定搜索参数到计数查询
if (!empty($status)) {
    $count_stmt->bindParam(':status', $status);
}
if (!empty($start_date)) {
    $count_stmt->bindParam(':start_date', $start_date);
}
if (!empty($end_date)) {
    $count_stmt->bindParam(':end_date', $end_date);
}
if (!empty($search)) {
    $search_param = "%$search%";
    $count_stmt->bindParam(':search', $search_param);
}

$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 计算总页数
$total_pages = ceil($total_records / $records_per_page);
$page = min(max(1, $page), $total_pages); // 确保页码在有效范围内

// 修改主查询添加分页
$query .= " LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);

// 绑定分页参数
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// 绑定参数
if (!empty($status)) {
    $stmt->bindParam(':status', $status);
}
if (!empty($start_date)) {
    $stmt->bindParam(':start_date', $start_date);
}
if (!empty($end_date)) {
    $stmt->bindParam(':end_date', $end_date);
}
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>订单管理</h2>
    </div>

    <!-- 筛选表单 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label class="form-label">开始日期</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">结束日期</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">搜索</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="订单号/客户/电话/商品" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">搜索</button>
                        <a href="orders.php" class="btn btn-secondary">重置</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 订单列表 -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>订单编号</th>
                            <th>客户信息</th>
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
                                <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($order['phone'] ?? '无电话'); ?><br>
                                    <?php echo htmlspecialchars($order['address'] ?? '无地址'); ?>
                                </small>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" 
                                     title="<?php echo htmlspecialchars($order['product_names']); ?>">
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
                                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        查看详情
                                    </a>
                                    <?php if ($order['status'] === '已付款'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success"
                                            onclick="updateOrderStatus(<?php echo $order['id']; ?>, '已发货')">
                                        发货
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($order['status'] === '已发货'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success"
                                            onclick="updateOrderStatus(<?php echo $order['id']; ?>, '已完成')">
                                        完成
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- 分页控件 -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="d-flex align-items-center">
                        <label class="me-2">每页显示：</label>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                            <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10条</option>
                            <option value="20" <?php echo $records_per_page == 20 ? 'selected' : ''; ?>>20条</option>
                            <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50条</option>
                        </select>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="订单列表分页">
                        <ul class="pagination mb-0">
                            <!-- 首页 -->
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=1&per_page=<?php echo $records_per_page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($start_date) ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo !empty($end_date) ? '&end_date=' . urlencode($end_date) : ''; ?>">首页</a>
                            </li>
                            
                            <!-- 上一页 -->
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $records_per_page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($start_date) ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo !empty($end_date) ? '&end_date=' . urlencode($end_date) : ''; ?>">上一页</a>
                            </li>

                            <!-- 页码 -->
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $records_per_page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($start_date) ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo !empty($end_date) ? '&end_date=' . urlencode($end_date) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>

                            <!-- 下一页 -->
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $records_per_page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($start_date) ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo !empty($end_date) ? '&end_date=' . urlencode($end_date) : ''; ?>">下一页</a>
                            </li>

                            <!-- 末页 -->
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>&per_page=<?php echo $records_per_page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($start_date) ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo !empty($end_date) ? '&end_date=' . urlencode($end_date) : ''; ?>">末页</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
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

function changePerPage(value) {
    let url = new URL(window.location.href);
    url.searchParams.set('page', '1');
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}
</script>

<?php include '../includes/footer.php'; ?> 