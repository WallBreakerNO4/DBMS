<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>库存管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">库存管理系统</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/products">商品管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/inventory">库存管理</a>
                    </li>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/suppliers">供应商管理</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/generate_code.php">注册码管理</a>
                        </li>
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
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/change_password.php">修改密码</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/logout.php">退出</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/login.php">登录</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/register.php">注册</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4"> 