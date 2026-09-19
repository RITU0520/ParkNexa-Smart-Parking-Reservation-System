CREATE DATABASE IF NOT EXISTS smart_parking
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_parking;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE parking_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(80) NOT NULL,
    total_slots INT UNSIGNED NOT NULL,
    available_slots INT UNSIGNED NOT NULL,
    hourly_rate DECIMAL(10,2) NOT NULL,
    opening_time TIME NOT NULL DEFAULT '06:00:00',
    closing_time TIME NOT NULL DEFAULT '23:00:00',
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE parking_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id INT UNSIGNED NOT NULL,
    slot_number VARCHAR(20) NOT NULL,
    slot_type ENUM('Regular','Accessible','EV') NOT NULL DEFAULT 'Regular',
    status ENUM('Available','Maintenance') NOT NULL DEFAULT 'Available',
    UNIQUE KEY uq_location_slot (location_id, slot_number),
    CONSTRAINT fk_slot_location FOREIGN KEY (location_id)
        REFERENCES parking_locations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NOT NULL,
    slot_id INT UNSIGNED NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    vehicle_type ENUM('Car','Bike','SUV','Other') NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Confirmed','Cancelled','Completed') NOT NULL DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_res_location FOREIGN KEY (location_id) REFERENCES parking_locations(id) ON DELETE RESTRICT,
    CONSTRAINT fk_res_slot FOREIGN KEY (slot_id) REFERENCES parking_slots(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO parking_locations
(name,address,city,total_slots,available_slots,hourly_rate,opening_time,closing_time)
VALUES
('Metro Central Parking','Rajiv Chowk Metro Gate 2','New Delhi',120,120,40.00,'05:30:00','23:30:00'),
('Connaught Place Smart Parking','B-Block, Connaught Place','New Delhi',80,80,60.00,'06:00:00','23:00:00'),
('Tech Park Parking','Sector 62 Main Road','Noida',150,150,30.00,'06:00:00','22:30:00'),
('Mall Road Parking','Near City Mall','Ghaziabad',100,100,35.00,'07:00:00','23:00:00');

INSERT INTO parking_slots (location_id,slot_number,slot_type)
SELECT id, CONCAT('A-', n), 'Regular'
FROM parking_locations CROSS JOIN
(SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) nums;
