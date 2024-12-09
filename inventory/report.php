<?php
session_start();
require_once '../config/database.php';
include '../includes/header.php';

// 设置时区为中国时区
date_default_timezone_set('Asia/Shanghai');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// 获取时间范围(如果没有GET参数,使用默认范围)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// 获取库存变动统计
$query = "SELECT 
            p.name as product_name,
            c.name as category_name,
            p.stock_quantity as current_stock,
            SUM(CASE WHEN ir.type = '入库' THEN ir.quantity ELSE 0 END) as total_in,
            SUM(CASE WHEN ir.type = '出库' THEN ir.quantity ELSE 0 END) as total_out,
            COUNT(DISTINCT CASE WHEN ir.type = '入库' THEN ir.id END) as in_count,
            COUNT(DISTINCT CASE WHEN ir.type = '出库' THEN ir.id END) as out_count
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN inventory_records ir ON p.id = ir.product_id 
            AND DATE(ir.created_at) BETWEEN :start_date AND :end_date
          GROUP BY p.id, p.name, c.name, p.stock_quantity
          ORDER BY p.name";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取汇总数据
$summary_query = "SELECT 
                    COUNT(DISTINCT CASE WHEN type = '入库' THEN id END) as total_in_records,
                    COUNT(DISTINCT CASE WHEN type = '出库' THEN id END) as total_out_records,
                    SUM(CASE WHEN type = '入库' THEN quantity ELSE 0 END) as total_in_quantity,
                    SUM(CASE WHEN type = '出库' THEN quantity ELSE 0 END) as total_out_quantity
                 FROM inventory_records
                 WHERE DATE(created_at) BETWEEN :start_date AND :end_date";

$stmt = $db->prepare($summary_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// 处理空值,避免PHP Notice
$summary['total_in_records'] = $summary['total_in_records'] ?? 0;
$summary['total_out_records'] = $summary['total_out_records'] ?? 0;
$summary['total_in_quantity'] = $summary['total_in_quantity'] ?? 0;
$summary['total_out_quantity'] = $summary['total_out_quantity'] ?? 0;
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>库存统计报表</h2>
        <div>
            <a href="index.php" class="btn btn-secondary">返回</a>
            <button onclick="exportToExcel()" class="btn btn-success">导出Excel</button>
        </div>
    </div>

    <!-- 时间范围选择 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3" id="reportForm">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">开始日期</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">结束日期</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">查询</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 汇总信息 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">入库次数</h5>
                    <p class="card-text h3"><?php echo $summary['total_in_records']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">入库总数量</h5>
                    <p class="card-text h3"><?php echo $summary['total_in_quantity']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">出库次数</h5>
                    <p class="card-text h3"><?php echo $summary['total_out_records']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">出库总数量</h5>
                    <p class="card-text h3"><?php echo $summary['total_out_quantity']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 详细报表 -->
    <div class="card">
        <div class="card-header">
            <h5>商品库存变动明细</h5>
        </div>
        <div class="card-body">
            <table class="table" id="reportTable">
                <thead>
                    <tr>
                        <th>商品名称</th>
                        <th>类别</th>
                        <th>当前库存</th>
                        <th>入库次数</th>
                        <th>入库总量</th>
                        <th>出库次数</th>
                        <th>出库总量</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($report['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($report['category_name']); ?></td>
                        <td><?php echo $report['current_stock']; ?></td>
                        <td><?php echo $report['in_count'] ?? 0; ?></td>
                        <td><?php echo $report['total_in'] ?? 0; ?></td>
                        <td><?php echo $report['out_count'] ?? 0; ?></td>
                        <td><?php echo $report['total_out'] ?? 0; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// 导出Excel功能
function exportToExcel() {
    // 获取表格数据
    let table = document.getElementById("reportTable");
    let html = table.outerHTML;
    
    // 创建一个Blob对象
    let blob = new Blob([html], {type: 'application/vnd.ms-excel'});
    
    // 创建一个下载链接
    let downloadLink = document.createElement("a");
    let url = URL.createObjectURL(blob);
    let isSafariBrowser = navigator.userAgent.indexOf('Safari') != -1 && navigator.userAgent.indexOf('Chrome') == -1;
    
    // 设置文件名
    let fileName = `库存报表_${document.getElementById('start_date').value}_${document.getElementById('end_date').value}.xls`;
    
    if (isSafariBrowser) {  // 如果是Safari浏览器
        downloadLink.setAttribute("target", "_blank");
    }
    
    downloadLink.href = url;
    downloadLink.download = fileName;
    downloadLink.click();
}

// 日期选择器验证
document.getElementById('reportForm').addEventListener('submit', function(e) {
    const startDate = new Date(document.getElementById('start_date').value);
    const endDate = new Date(document.getElementById('end_date').value);
    
    if (startDate > endDate) {
        e.preventDefault();
        alert('开始日期不能大于结束日期');
    }
});
</script>

<?php include '../includes/footer.php'; ?> 