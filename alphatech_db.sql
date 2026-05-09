CREATE DATABASE IF NOT EXISTS alphatech_db;
USE alphatech_db;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    fullname   VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

CREATE TABLE IF NOT EXISTS categories (
   id     INT AUTO_INCREMENT PRIMARY KEY,
   category_name VARCHAR(100) NOT NULL
    );

CREATE TABLE IF NOT EXISTS products (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(100) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL,
    stock       INT DEFAULT 0,
    category_id INT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    );

CREATE TABLE IF NOT EXISTS orders (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    order_date   DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    status       VARCHAR(80) DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    );

CREATE TABLE IF NOT EXISTS cart (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT,
    product_id INT NOT NULL,
    quantity   INT NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    );


INSERT INTO categories (category_name) VALUES
    ('Router'),
    ('Server'),
    ('Workstation'),
    ('GPU'),
    ('Switch');


INSERT INTO products (name, description, price, stock, category_id) VALUES
    ('Tenda AC6 Smart WiFi Router',         'Dual Band 2.4GHz & 5GHz, 1200Mbps',          200000.00, 20, 1),
    ('Dell PowerEdge R760 Server',          '2U rack server, Intel Xeon, 32GB RAM',        100000.00,  5, 2),
    ('IPS LCD Gaming Monitor',              '27-inch 144Hz IPS panel',                      75000.00, 10, 3),
    ('Asus ROG RTX 3050 8GB Graphics Card', 'GDDR6 128-bit, NVIDIA GeForce RTX 3050',       50000.00,  8, 4),
    ('Cisco Managed Switch 24-Port',        '24-port gigabit managed switch',               12500.00, 15, 5),
    ('Rack Mount Server',                   'Enterprise-grade 2U rack server',              75000.00,  3, 2),
    ('High-End Workstation PC',             'For CAD, 3D rendering, and video editing',     45000.00,  7, 3);