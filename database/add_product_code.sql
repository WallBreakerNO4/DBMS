ALTER TABLE `products` 
ADD COLUMN `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品编码' AFTER `id`,
ADD UNIQUE INDEX `idx_product_code`(`code`);

-- 为现有商品生成编码
UPDATE `products` SET `code` = CONCAT('P', LPAD(id, 6, '0')) WHERE `code` IS NULL; 