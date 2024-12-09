CREATE TABLE registration_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(32) NOT NULL UNIQUE,
    used BOOLEAN DEFAULT FALSE,
    used_by INT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    FOREIGN KEY (used_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
); 