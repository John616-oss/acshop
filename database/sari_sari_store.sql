CREATE DATABASE IF NOT EXISTS sari_sari_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sari_sari_store;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS restock_items;
DROP TABLE IF EXISTS restock_requests;
DROP TABLE IF EXISTS email_verifications;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','staff','cashier') NOT NULL DEFAULT 'cashier',
 is_verified TINYINT(1) NOT NULL DEFAULT 0,
 verified_at DATETIME NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE email_verifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL,
 used_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_verification_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_verification_expiry(expires_at)
) ENGINE=InnoDB;

CREATE TABLE categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 description VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE suppliers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL,
 contact_name VARCHAR(120) NULL,
 phone VARCHAR(40) NULL,
 email VARCHAR(190) NULL,
 address VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_code VARCHAR(50) NOT NULL UNIQUE,
 name VARCHAR(150) NOT NULL,
 category_id INT UNSIGNED NULL,
 cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 stock_quantity INT NOT NULL DEFAULT 0,
 reorder_level INT UNSIGNED NOT NULL DEFAULT 5,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_product_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
 CONSTRAINT chk_product_cost CHECK(cost_price >= 0),
 CONSTRAINT chk_product_price CHECK(selling_price >= 0),
 CONSTRAINT chk_product_stock CHECK(stock_quantity >= 0),
 INDEX idx_product_name(name),
 INDEX idx_product_stock(stock_quantity)
) ENGINE=InnoDB;

CREATE TABLE restock_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 requested_by INT UNSIGNED NOT NULL,
 supplier_id INT UNSIGNED NULL,
 status ENUM('pending','approved','rejected','received') NOT NULL DEFAULT 'pending',
 notes VARCHAR(255) NULL,
 reviewed_by INT UNSIGNED NULL,
 reviewed_at DATETIME NULL,
 received_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_restock_requester FOREIGN KEY(requested_by) REFERENCES users(id),
 CONSTRAINT fk_restock_supplier FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
 CONSTRAINT fk_restock_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id),
 INDEX idx_restock_status(status,created_at)
) ENGINE=InnoDB;

CREATE TABLE restock_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 request_id BIGINT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NOT NULL,
 quantity_requested INT UNSIGNED NOT NULL,
 CONSTRAINT fk_restock_item_request FOREIGN KEY(request_id) REFERENCES restock_requests(id) ON DELETE CASCADE,
 CONSTRAINT fk_restock_item_product FOREIGN KEY(product_id) REFERENCES products(id),
 CONSTRAINT chk_restock_qty CHECK(quantity_requested > 0)
) ENGINE=InnoDB;

CREATE TABLE sales (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 receipt_no VARCHAR(50) NOT NULL UNIQUE,
 cashier_id INT UNSIGNED NOT NULL,
 total DECIMAL(12,2) NOT NULL,
 payment_received DECIMAL(12,2) NOT NULL,
 change_amount DECIMAL(12,2) NOT NULL,
 status ENUM('completed','voided') NOT NULL DEFAULT 'completed',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_sale_cashier FOREIGN KEY(cashier_id) REFERENCES users(id),
 INDEX idx_sales_date_status(created_at,status)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 sale_id BIGINT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 unit_price DECIMAL(10,2) NOT NULL,
 unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 line_total DECIMAL(12,2) NOT NULL,
 CONSTRAINT fk_sale_item_sale FOREIGN KEY(sale_id) REFERENCES sales(id),
 CONSTRAINT fk_sale_item_product FOREIGN KEY(product_id) REFERENCES products(id),
 CONSTRAINT chk_sale_item_qty CHECK(quantity > 0),
 INDEX idx_sale_items_product(product_id)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id INT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 movement_type ENUM('in','out','adjustment','sale','restock') NOT NULL,
 quantity_change INT NOT NULL,
 reference_type VARCHAR(40) NULL,
 reference_id BIGINT UNSIGNED NULL,
 notes VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_movement_product FOREIGN KEY(product_id) REFERENCES products(id),
 CONSTRAINT fk_movement_user FOREIGN KEY(user_id) REFERENCES users(id),
 INDEX idx_movement_product_date(product_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NULL,
 action VARCHAR(60) NOT NULL,
 entity_type VARCHAR(60) NULL,
 entity_id BIGINT NULL,
 details VARCHAR(500) NULL,
 ip_address VARCHAR(45) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_activity_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_activity_date(created_at),
 INDEX idx_activity_entity(entity_type,entity_id)
) ENGINE=InnoDB;

INSERT INTO categories(name,description) VALUES
('Instant Noodles','Instant noodles and quick meals'),
('Snacks','Chips, biscuits, and candies'),
('Drinks','Bottled drinks and powdered beverages'),
('Household','Daily household essentials');

INSERT INTO suppliers(name,contact_name,phone,email,address) VALUES
('Sample General Merchandise','Juan Dela Cruz','09170000001','supplier@example.com','Local supplier - replace with your sample data');

INSERT INTO products(product_code,name,category_id,cost_price,selling_price,stock_quantity,reorder_level) VALUES
('NOODLE001','Instant Noodles',1,11.00,15.00,40,10),
('SNACK001','Potato Chips',2,8.00,12.00,25,8),
('BISCUIT01','Cream Biscuits',2,6.00,10.00,30,8),
('DRINK001','Bottled Water',3,8.00,12.00,20,5),
('COFFEE001','Instant Coffee Sachet',3,5.00,7.00,50,10),
('SOAP001','Laundry Soap',4,15.00,20.00,12,5);
