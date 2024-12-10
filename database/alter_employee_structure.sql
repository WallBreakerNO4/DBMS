-- 创建部门表
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入一些基础部门数据
INSERT INTO departments (name, description) VALUES 
('人事部', '负责人力资源管理'),
('财务部', '负责公司财务管理'),
('仓储部', '负责仓库管理'),
('采购部', '负责采购管理'),
('销售部', '负责销售管理');

-- 修改employees表，添加department_id、phone和address字段
ALTER TABLE employees 
ADD COLUMN department_id INT,
ADD COLUMN phone VARCHAR(20),
ADD COLUMN address TEXT,
ADD CONSTRAINT employees_department_fk 
FOREIGN KEY (department_id) REFERENCES departments(id); 