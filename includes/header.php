<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'supplier', 'employee'])) ? '库存管理系统' : '商品展示系统'; ?></title>
    <link rel="icon" href="/favicon/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/favicon/site.webmanifest">
    <link rel="mask-icon" href="/favicon/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="/favicon/favicon.svg" alt="Logo" width="24" height="24" class="d-inline-block align-text-top me-2">
                <?php echo (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'supplier', 'employee'])) ? '库存管理系统' : '商品展示系统'; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/products/display.php">商品展示</a>
                    </li>
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/cart">
                                购物车 <span class="badge bg-primary" id="cartCount">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/orders">我的订单</a>
                        </li>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'supplier', 'employee'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/products">商品管理</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/inventory">库存管理</a>
                        </li>
                        <?php if($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/suppliers">供应商管理</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/employees">员工管理</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/generate_code.php">注册码管理</a>
                            </li>
                        <?php endif; ?>
                        <?php if(in_array($_SESSION['role'], ['admin', 'employee'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/orders.php">订单管理</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'supplier'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/auth/profile.php">供应商资料</a>
                            </li>
                        <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === 'employee'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/auth/employee_profile.php">员工资料</a>
                            </li>
                        <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/auth/customer_profile.php">客户资料</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/change_password.php">修改密码</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/logout.php">退出登录</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/register.php">用户注册</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/login.php">用户登录</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4"> 