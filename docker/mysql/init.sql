CREATE DATABASE IF NOT EXISTS safeshareprod;
USE safeshareprod;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

INSERT IGNORE INTO users (name, email, password, role) 
VALUES ('Admin', 'admin@safeshare.com', '$2y$10$SQw0SL6FEZ/C7WjWZDHNku.qDVDRs11wbDQHytamYUmEcRSSuKowS', 'admin');