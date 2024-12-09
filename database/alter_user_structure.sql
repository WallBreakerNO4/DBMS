-- 临时禁用外键检查
SET FOREIGN_KEY_CHECKS = 0;

-- 删除可能存在的表（注意顺序：先删除子表，再删除父表）
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS persons;

-- 首先创建一个基础的persons表作为父表
CREATE TABLE persons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'employee', 'supplier', 'customer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 创建employees表（对应原users表中的admin和employee角色）
CREATE TABLE employees (
    person_id INT PRIMARY KEY,
    employee_number VARCHAR(20) UNIQUE,
    department VARCHAR(50),
    position VARCHAR(50),
    FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE
);

-- 修改suppliers表结构
DROP TABLE IF EXISTS suppliers;
CREATE TABLE suppliers (
    person_id INT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    contact_name VARCHAR(50),
    phone VARCHAR(20),
    address TEXT,
    FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE
);

-- 创建新的customers表
CREATE TABLE customers (
    person_id INT PRIMARY KEY,
    membership_level VARCHAR(20),
    points INT DEFAULT 0,
    phone VARCHAR(20),
    address TEXT,
    FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE
);

-- 更新相关外键引用
-- 先删除旧的外键约束
ALTER TABLE inventory_records 
DROP FOREIGN KEY IF EXISTS inventory_records_ibfk_2;

ALTER TABLE registration_codes 
DROP FOREIGN KEY IF EXISTS registration_codes_ibfk_1,
DROP FOREIGN KEY IF EXISTS registration_codes_ibfk_2;

-- 添加新的外键约束
ALTER TABLE inventory_records 
ADD CONSTRAINT inventory_records_operator_fk 
FOREIGN KEY (operator_id) REFERENCES persons(id);

ALTER TABLE registration_codes 
ADD CONSTRAINT registration_codes_used_by_fk 
FOREIGN KEY (used_by) REFERENCES persons(id),
ADD CONSTRAINT registration_codes_created_by_fk 
FOREIGN KEY (created_by) REFERENCES persons(id);

-- 重新启用外键检查
SET FOREIGN_KEY_CHECKS = 1; 