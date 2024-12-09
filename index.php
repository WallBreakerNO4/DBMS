<?php
session_start();
require_once 'config/database.php';
include 'includes/header.php';
?>

<div class="jumbotron">
    <h1 class="display-4">欢迎使用库存管理系统</h1>
    <p class="lead">这是一个完整的库存管理解决方案，帮助您更好地管理商品、供应商和库存。</p>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">商品管理</h5>
                <p class="card-text">管理所有商品信息，包括添加新商品、修改现有商品信息等。</p>
                <a href="/products" class="btn btn-primary">进入管理</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">库存管理</h5>
                <p class="card-text">查看和管理商品库存，处理入库和出库操作。</p>
                <a href="/inventory" class="btn btn-primary">进入管理</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">供应商管理</h5>
                <p class="card-text">管理供应商信息，查看采购历史记录。</p>
                <a href="/suppliers" class="btn btn-primary">进入管理</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 