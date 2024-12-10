-- 添加supplier_id字段到products表
ALTER TABLE products
ADD COLUMN supplier_id INT,
ADD CONSTRAINT products_supplier_fk 
FOREIGN KEY (supplier_id) REFERENCES persons(id);

-- 添加索引以提高查询性能
CREATE INDEX idx_products_supplier ON products(supplier_id); 