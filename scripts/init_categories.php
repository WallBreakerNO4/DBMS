<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// 预设类别数据
$categories = [
    ['即食食品', '包括便当、三明治、寿司等即食食品'],
    ['零食小吃', '薯片、饼干、糖果等休闲零食'],
    ['饮料', '碳酸饮料、果汁、茶饮、咖啡等'],
    ['乳制品', '牛奶、酸奶、奶酪等乳制品'],
    ['冷冻食品', '冰淇淋、冷冻点心、速冻食品等'],
    ['面包糕点', '新鲜面包、蛋糕、甜点等'],
    ['个人护理', '牙膏、牙刷、沐浴露、洗发水等'],
    ['生活用品', '纸巾、垃圾袋、清洁用品等'],
    ['文具用品', '笔、本子、胶带等办公文具'],
    ['烟酒', '香烟、啤酒、酒类等'],
    ['杂志报刊', '报纸、杂志、书籍等'],
    ['季节性商品', '根据节日和季节推出的限定商品'],
    ['便民服务', '快递、充值、打印等服务'],
    ['其他', '其他未分类商品']
];

try {
    // 禁用外键检查
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // 清空现有类别
    $db->exec('TRUNCATE TABLE categories');
    
    // 插入新类别
    $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
    $stmt = $db->prepare($query);
    
    foreach ($categories as $category) {
        $stmt->bindParam(':name', $category[0]);
        $stmt->bindParam(':description', $category[1]);
        $stmt->execute();
    }
    
    // 重新启用外键检查
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "成功添加预设类别！\n";
} catch (PDOException $e) {
    echo "添加类别失败: " . $e->getMessage() . "\n";
    // 确保在发生错误时也重新启用外键检查
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
}
?> 