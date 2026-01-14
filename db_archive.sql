CREATE DATABASE IF NOT EXISTS archive_db;
USE archive_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT 'ph-folder'
);

-- Seed Categories
INSERT INTO categories (name, slug, icon) VALUES 
('Anggaran', 'anggaran', 'ph-money'),
('Realisasi Anggaran', 'realisasi-anggaran', 'ph-chart-line-up'),
('SPJ', 'spj', 'ph-receipt'),
('BKU', 'bku', 'ph-book'),
('Belanja Modal', 'bm', 'ph-buildings'),
('Bahan Bakar Minyak', 'bbm', 'ph-gas-pump'),
('Barang & Jasa', 'banjas', 'ph-package'),
('Pemeliharaan', 'pemeliharaan', 'ph-wrench')
ON DUPLICATE KEY UPDATE icon=VALUES(icon);

-- Documents Table
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    doc_date DATE NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL, -- pdf, image, excel, word, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
