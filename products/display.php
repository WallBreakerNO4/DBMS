<?php
session_start();
require_once '../config/database.php';
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// 获取排序参数
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [12, 24, 48]) ? (int)$_GET['per_page'] : 24;

// 计算偏移量
$offset = ($page - 1) * $records_per_page;

// 构建查询
$query = "SELECT p.*, c.name as category_name, s.company_name as supplier_name
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN suppliers s ON p.supplier_id = s.person_id
          WHERE 1=1";

// 添加搜索条件
if (!empty($search)) {
    $query .= " AND (p.name LIKE :search 
                OR p.description LIKE :search 
                OR c.name LIKE :search 
                OR s.company_name LIKE :search)";
}

// 添加排序
switch ($sort_by) {
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'stock_asc':
        $query .= " ORDER BY p.stock_quantity ASC";
        break;
    case 'stock_desc':
        $query .= " ORDER BY p.stock_quantity DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    default:
        $query .= " ORDER BY p.name ASC";
}

// 获取总记录数
$count_query = "SELECT COUNT(*) as total FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN suppliers s ON p.supplier_id = s.person_id
                WHERE 1=1";

if (!empty($search)) {
    $count_query .= " AND (p.name LIKE :search 
                    OR p.description LIKE :search 
                    OR c.name LIKE :search 
                    OR s.company_name LIKE :search)";
}

$count_stmt = $db->prepare($count_query);
if (!empty($search)) {
    $search_param = "%{$search}%";
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

// 绑定所有参数
if (!empty($search)) {
    $stmt->bindParam(':search', $search_param);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2>商品展示</h2>
        </div>
    </div>

    <!-- 搜索和排序区域 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form class="d-flex" method="GET">
                <input class="form-control me-2" type="search" name="search" 
                       placeholder="搜索商品名称、描述、类别或供应商" 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-primary" type="submit">搜索</button>
            </form>
        </div>
        <div class="col-md-6">
            <div class="btn-group float-end">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                    排序方式
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?sort=name_asc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">名称 (A-Z)</a></li>
                    <li><a class="dropdown-item" href="?sort=name_desc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">名称 (Z-A)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?sort=price_asc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">价格 (低到高)</a></li>
                    <li><a class="dropdown-item" href="?sort=price_desc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">价格 (高到低)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?sort=stock_asc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">库存 (低到高)</a></li>
                    <li><a class="dropdown-item" href="?sort=stock_desc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">库存 (高到低)</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 商品展示区域 -->
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php while ($product = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </h6>
                    <p class="card-text">
                        <?php echo htmlspecialchars($product['description']); ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-primary h5">￥<?php echo number_format($product['price'], 2); ?></span>
                        <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                            <?php echo $product['stock_quantity'] > 0 ? '库存: ' . $product['stock_quantity'] : '无货'; ?>
                        </span>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">供应商: <?php echo htmlspecialchars($product['supplier_name'] ?? '未指定'); ?></small>
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <a href="/auth/login.php" class="btn btn-primary btn-sm">购买</a>
                            <?php elseif ($_SESSION['role'] === 'customer'): ?>
                                <div class="d-flex align-items-center">
                                    <input type="number" class="form-control form-control-sm me-2" 
                                           id="quantity_<?php echo $product['id']; ?>" 
                                           style="width: 60px;" 
                                           min="1" 
                                           max="<?php echo $product['stock_quantity']; ?>" 
                                           value="1">
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="addToCart(<?php echo $product['id']; ?>)">
                                        购买
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled>无货</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- 分页控件 -->
    <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="d-flex align-items-center">
            <label class="me-2">每页显示：</label>
            <select class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                <option value="12" <?php echo $records_per_page == 12 ? 'selected' : ''; ?>>12件</option>
                <option value="24" <?php echo $records_per_page == 24 ? 'selected' : ''; ?>>24件</option>
                <option value="48" <?php echo $records_per_page == 48 ? 'selected' : ''; ?>>48件</option>
            </select>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="商品列表分页">
            <ul class="pagination mb-0">
                <!-- 首页 -->
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=1&per_page=<?php echo $records_per_page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($sort_by) ? '&sort=' . urlencode($sort_by) : ''; ?>">首页</a>
                </li>
                
                <!-- 上一页 -->
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $records_per_page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($sort_by) ? '&sort=' . urlencode($sort_by) : ''; ?>">上一页</a>
                </li>

                <!-- 页码 -->
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $records_per_page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($sort_by) ? '&sort=' . urlencode($sort_by) : ''; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>

                <!-- 下一页 -->
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $records_per_page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($sort_by) ? '&sort=' . urlencode($sort_by) : ''; ?>">下一页</a>
                </li>

                <!-- 末页 -->
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $total_pages; ?>&per_page=<?php echo $records_per_page; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($sort_by) ? '&sort=' . urlencode($sort_by) : ''; ?>">末页</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <!-- 添加购物相关的JavaScript代码 -->
    <script>
    function addToCart(productId) {
        const quantity = document.getElementById(`quantity_${productId}`).value;
        
        fetch('/cart/add_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('已添加到购物车！');
                updateCartCount(); // 更新购物车数量显示
            } else {
                alert(data.message || '添加失败，请重试');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('操作失败，请重试');
        });
    }

    // 更新购物车数量显示
    function updateCartCount() {
        fetch('/cart/get_count.php')
            .then(response => response.json())
            .then(data => {
                const cartCount = document.getElementById('cartCount');
                if (cartCount) {
                    cartCount.textContent = data.count;
                }
            });
    }

    // 页面加载时更新购物车数量
    document.addEventListener('DOMContentLoaded', updateCartCount);

    function changePerPage(value) {
        let url = new URL(window.location.href);
        url.searchParams.set('page', '1');
        url.searchParams.set('per_page', value);
        window.location.href = url.toString();
    }
    </script>
</div>

<?php include '../includes/footer.php'; ?> 