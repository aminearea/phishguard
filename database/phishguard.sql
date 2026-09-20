-- PhishGuard Database
CREATE DATABASE IF NOT EXISTS phishguard
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE phishguard;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Scans table
CREATE TABLE IF NOT EXISTS scans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  url TEXT NOT NULL,
  risk_score INT NOT NULL,
  risk_level VARCHAR(10) NOT NULL,
  reasons TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin: admin / admin123
-- (hash generated with password_hash('admin123', PASSWORD_DEFAULT))
INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$YKq8rQx0mYh7hZ1w8H1qOeK8kL6sJ0XzV9yQ4T3mN2pR7wS5uD6vC');