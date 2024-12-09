<?php
http_response_code(404);
include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 text-center">
            <h1 class="display-1">404</h1>
            <h2 class="mb-4">页面未找到</h2>
            <p class="lead mb-5">抱歉，您访问的页面不存在或没有访问权限。</p>
            <a href="/" class="btn btn-primary">返回首页</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 