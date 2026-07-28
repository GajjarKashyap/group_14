-- Database Schema for Perfume Hub

CREATE TABLE IF NOT EXISTS `perfume_houses` (
  `house_id` INT AUTO_INCREMENT PRIMARY KEY,
  `house_name` VARCHAR(255) NOT NULL,
  `origin_country` VARCHAR(100) DEFAULT 'France'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fragrance_collections` (
  `collection_id` INT AUTO_INCREMENT PRIMARY KEY,
  `collection_name` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fragrance_families` (
  `family_id` INT AUTO_INCREMENT PRIMARY KEY,
  `family_name` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fragrance_strengths` (
  `strength_id` INT AUTO_INCREMENT PRIMARY KEY,
  `strength_name` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `perfumers` (
  `perfumer_id` INT AUTO_INCREMENT PRIMARY KEY,
  `perfumer_name` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fragrances` (
  `fragrance_id` INT AUTO_INCREMENT PRIMARY KEY,
  `fragrance_code` VARCHAR(50) NOT NULL UNIQUE,
  `catalogue_number` VARCHAR(50) DEFAULT NULL,
  `fragrance_name` VARCHAR(255) NOT NULL,
  `url_key` VARCHAR(255) DEFAULT NULL,
  `house_id` INT NOT NULL,
  `collection_id` INT DEFAULT NULL,
  `segment_id` INT DEFAULT NULL,
  `audience` VARCHAR(50) DEFAULT 'Unisex',
  `family_id` INT DEFAULT NULL,
  `strength_id` INT DEFAULT NULL,
  `perfumer_id` INT DEFAULT NULL,
  `primary_volume_ml` INT DEFAULT 100,
  `summary` TEXT DEFAULT NULL,
  `full_story` TEXT DEFAULT NULL,
  `origin_country` VARCHAR(100) DEFAULT 'France',
  `release_year` INT DEFAULT 2023,
  `list_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `offer_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_rate` DECIMAL(5,2) DEFAULT 0.00,
  `available_units` INT DEFAULT 100,
  `availability_state` VARCHAR(50) DEFAULT 'Ready',
  `record_state` VARCHAR(50) DEFAULT 'Published',
  `data_quality_score` INT DEFAULT 100,
  `featured_flag` TINYINT(1) DEFAULT 0,
  `view_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fragrance_notes` (
  `note_id` INT AUTO_INCREMENT PRIMARY KEY,
  `fragrance_id` INT NOT NULL,
  `note_stage` VARCHAR(50) NOT NULL,
  `ingredient_name` VARCHAR(255) NOT NULL,
  `display_position` INT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fragrance_volumes` (
  `volume_id` INT AUTO_INCREMENT PRIMARY KEY,
  `fragrance_id` INT NOT NULL,
  `volume_label` VARCHAR(50) NOT NULL,
  `volume_ml` INT NOT NULL,
  `volume_price` DECIMAL(10,2) NOT NULL,
  `volume_list_price` DECIMAL(10,2) NOT NULL,
  `is_default_volume` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fragrance_editions` (
  `edition_id` INT AUTO_INCREMENT PRIMARY KEY,
  `fragrance_id` INT NOT NULL,
  `edition_title` VARCHAR(255) NOT NULL,
  `edition_details` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fragrance_media` (
  `media_id` INT AUTO_INCREMENT PRIMARY KEY,
  `fragrance_id` INT NOT NULL,
  `remote_image_address` TEXT NOT NULL,
  `media_origin` VARCHAR(50) DEFAULT 'external_link',
  `image_type` VARCHAR(50) DEFAULT 'Main',
  `featured_image` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) DEFAULT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(50) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `purchase_id` INT AUTO_INCREMENT PRIMARY KEY,
  `purchase_reference` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `recipient_name` VARCHAR(255) NOT NULL,
  `recipient_phone` VARCHAR(50) NOT NULL,
  `delivery_line_one` VARCHAR(255) NOT NULL,
  `delivery_line_two` VARCHAR(255) DEFAULT NULL,
  `delivery_city` VARCHAR(100) NOT NULL,
  `delivery_state` VARCHAR(100) NOT NULL,
  `delivery_postcode` VARCHAR(20) NOT NULL,
  `items_value` DECIMAL(10,2) NOT NULL,
  `payable_value` DECIMAL(10,2) NOT NULL,
  `payment_choice` VARCHAR(50) NOT NULL DEFAULT 'COD',
  `payment_state` VARCHAR(50) DEFAULT 'Pending',
  `fulfilment_state` VARCHAR(50) DEFAULT 'Placed',
  `placed_on` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `purchase_lines` (
  `line_id` INT AUTO_INCREMENT PRIMARY KEY,
  `purchase_id` INT NOT NULL,
  `fragrance_id` INT DEFAULT NULL,
  `fragrance_name_snapshot` VARCHAR(255) NOT NULL,
  `fragrance_code_snapshot` VARCHAR(50) NOT NULL,
  `house_name_snapshot` VARCHAR(255) NOT NULL,
  `volume_snapshot` VARCHAR(50) DEFAULT NULL,
  `edition_snapshot` VARCHAR(255) DEFAULT NULL,
  `units` INT NOT NULL DEFAULT 1,
  `price_per_unit` DECIMAL(10,2) NOT NULL,
  `line_value` DECIMAL(10,2) NOT NULL,
  `image_snapshot` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payment_records` (
  `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `purchase_id` INT NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `transaction_ref` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_status` VARCHAR(50) DEFAULT 'Completed',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(255) DEFAULT 'Perfume Hub',
  `category` VARCHAR(100) DEFAULT 'Unisex',
  `mrp` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `price` DECIMAL(10,2) NOT NULL,
  `description` TEXT,
  `image_url` TEXT,
  `image_urls` TEXT,
  `availability` VARCHAR(50) DEFAULT 'Ready'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Lookup Tables
INSERT IGNORE INTO `perfume_houses` (`house_id`, `house_name`, `origin_country`) VALUES
(1, 'Creed', 'France'),
(2, 'Tom Ford', 'United States'),
(3, 'Dior', 'France'),
(4, 'Lattafa', 'United Arab Emirates');

INSERT IGNORE INTO `fragrance_collections` (`collection_id`, `collection_name`) VALUES
(1, 'Signature Collection');

INSERT IGNORE INTO `fragrance_families` (`family_id`, `family_name`) VALUES
(1, 'Fresh'),
(2, 'Woody'),
(3, 'Floral'),
(4, 'Oriental/Amber'),
(6, 'Gourmand'),
(9, 'Spicy');

INSERT IGNORE INTO `fragrance_strengths` (`strength_id`, `strength_name`) VALUES
(1, 'Elixir'),
(2, 'Eau de Parfum'),
(3, 'Eau de Toilette');

INSERT IGNORE INTO `perfumers` (`perfumer_id`, `perfumer_name`) VALUES
(1, 'Olivier Creed'),
(2, 'Francois Demachy'),
(3, 'Calice Becker');
