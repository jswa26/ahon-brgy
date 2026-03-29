-- ============================================================
-- AHON-BRGY Database Setup
-- Run this in phpMyAdmin > SQL tab
-- ============================================================

CREATE DATABASE IF NOT EXISTS ahon_brgy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ahon_brgy_db;

-- ── HOUSEHOLDS
CREATE TABLE IF NOT EXISTS households (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  household_head VARCHAR(150) NOT NULL,
  address        VARCHAR(255),
  members        INT DEFAULT 1,
  monthly_income DECIMAL(12,2) DEFAULT 0,
  poverty_level  ENUM('Indigent','Low Income','Near Poor') DEFAULT 'Low Income',
  contact        VARCHAR(20),
  notes          TEXT,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── ASSISTANCE
CREATE TABLE IF NOT EXISTS assistance (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  household_id     INT NOT NULL,
  assistance_type  VARCHAR(100) NOT NULL,
  date_given       DATE NOT NULL,
  given_by         VARCHAR(100),
  amount           DECIMAL(12,2) DEFAULT 0,
  notes            TEXT,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
);

-- ── USERS
CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(60) UNIQUE NOT NULL,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('admin','staff') DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── DEFAULT ADMIN (password: admin123)
INSERT IGNORE INTO users (username, password, role)
VALUES ('admin', MD5('admin123'), 'admin');

-- ── SAMPLE STAFF
INSERT IGNORE INTO users (username, password, role)
VALUES ('staff1', MD5('staff123'), 'staff');

-- ── SAMPLE HOUSEHOLDS
INSERT IGNORE INTO households (household_head, address, members, monthly_income, poverty_level, contact) VALUES
('Juan Dela Cruz',     'Purok 1, Brgy. San Jose',  5, 4500,  'Indigent',   '0912-345-6789'),
('Maria Santos',       'Purok 2, Brgy. San Jose',  3, 8000,  'Low Income', '0923-456-7890'),
('Pedro Reyes',        'Purok 3, Brgy. San Jose',  7, 2000,  'Indigent',   '0934-567-8901'),
('Ana Bautista',       'Purok 1, Brgy. San Jose',  4, 12000, 'Near Poor',  '0945-678-9012'),
('Carlos Mendoza',     'Purok 4, Brgy. San Jose',  6, 3500,  'Low Income', '0956-789-0123'),
('Lita Villanueva',    'Purok 2, Brgy. San Jose',  2, 6000,  'Low Income', NULL),
('Roberto Aquino',     'Purok 5, Brgy. San Jose',  8, 1500,  'Indigent',   '0967-890-1234'),
('Elena Castillo',     'Purok 3, Brgy. San Jose',  4, 9500,  'Near Poor',  '0978-901-2345');

-- ── SAMPLE ASSISTANCE
INSERT IGNORE INTO assistance (household_id, assistance_type, date_given, given_by, amount) VALUES
(1, 'Food Pack',          CURDATE() - INTERVAL 10 DAY,  'admin', 500),
(2, 'Cash Assistance',    CURDATE() - INTERVAL 5 DAY,   'admin', 1000),
(3, 'Food Pack',          CURDATE() - INTERVAL 2 DAY,   'staff1', 500),
(1, 'Medical Assistance', CURDATE() - INTERVAL 30 DAY,  'admin', 2000),
(4, 'Educational Assistance', CURDATE() - INTERVAL 15 DAY, 'staff1', 1500),
(5, 'Livelihood Kit',     CURDATE() - INTERVAL 8 DAY,   'admin', 3000),
(3, 'Cash Assistance',    CURDATE() - INTERVAL 60 DAY,  'admin', 1000),
(7, 'Food Pack',          CURDATE() - INTERVAL 90 DAY,  'staff1', 500);
