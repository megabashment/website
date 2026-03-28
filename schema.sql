-- =====================================================================
-- REZEPT-WOCHENPLANER DATABASE SCHEMA
-- =====================================================================
--
-- Run this SQL script ONCE on your Strato database via phpMyAdmin.
-- It creates the three tables needed for the app.
-- This file is NOT deployed by the CI/CD pipeline.
--

-- ─────────────────────────────────────────────────────────────────────
-- Table: users
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(64) NOT NULL UNIQUE,
  `display_name` VARCHAR(128) NOT NULL,
  `email` VARCHAR(255) NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','user') NOT NULL DEFAULT 'user',
  `status` ENUM('pending','active') NOT NULL DEFAULT 'active',
  `reset_token` VARCHAR(64) NULL,
  `reset_token_expires` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Table: recipes (shared family recipe book)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE `recipes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` VARCHAR(64) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `ingredients` TEXT NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Table: week_plan_entries (per-user, one row per day)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE `week_plan_entries` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `day_name` VARCHAR(20) NOT NULL,
  `day_index` TINYINT UNSIGNED NOT NULL,
  `recipe_id` VARCHAR(64) NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_day` (`user_id`, `day_index`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Optional: Insert default recipes
-- ─────────────────────────────────────────────────────────────────────
-- WARNING: Only run this AFTER you have created your first admin user!
-- Otherwise the `created_by` foreign key will fail.
--
-- Uncomment the INSERT statements below and run them to seed default recipes.
-- Replace `1` with the actual admin user ID if it's different.
--

/*
INSERT INTO `recipes` (`client_id`, `name`, `ingredients`, `created_by`) VALUES
('def-1', 'Spaghetti Bolognese', 'Rinderhackfleisch (500g)\nSpaghetti (400g)\nZwiebel (1 groß)\nKnoblauch (3 Zehen)\nDosentomaten (400g)\nTomatenmark (2 EL)\nOlivenöl\nParmesan\nOregano\nSalz, Pfeffer', 1),
('def-2', 'Hähnchen-Curry', 'Hähnchenbrust (600g)\nKokosmilch (400ml)\nReis (300g)\nZwiebel\nKnoblauch\nIngwer (1 Stück)\nRote Paprika\nCurrypaste (2 EL)\nKoriander\nSalz', 1),
('def-3', 'Kartoffelgratin', 'Kartoffeln (1kg)\nSahne (200ml)\nGeriebener Käse (150g)\nKnoblauch (2 Zehen)\nButter\nMuskatnuss\nSalz, Pfeffer', 1),
('def-4', 'Pfannkuchen', 'Mehl (250g)\nMilch (500ml)\nEier (3 Stück)\nButter\nSalz\nZucker (1 EL)\nVanilleextrakt', 1),
('def-5', 'Lachs mit Ofengemüse', 'Lachsfilet (500g)\nZucchini\nKarotten (2 Stück)\nZitrone\nOlivenöl\nKnoblauch\nDill\nSalz, Pfeffer', 1),
('def-6', 'Gemüsesuppe', 'Karotten (3 Stück)\nSellerie\nLauch\nZwiebel\nKartoffeln (400g)\nGemüsebrühe (1.5 L)\nPetersilie\nSalz, Pfeffer', 1),
('def-7', 'Spinat-Ricotta-Pasta', 'Blattspinat (300g)\nRicotta (250g)\nPenne (400g)\nKnoblauch (2 Zehen)\nOlivenöl\nParmesan\nMuskatnuss\nSalz, Pfeffer', 1);
*/

-- ─────────────────────────────────────────────────────────────────────
-- Optional: Create initial admin user
-- ─────────────────────────────────────────────────────────────────────
-- WARNING: Change the username, display_name, and password hash!
-- Generate the bcrypt hash using password_hash("yourpassword", PASSWORD_BCRYPT) in PHP.
--
-- Once you have a PHP environment, you can use:
-- php -r 'echo password_hash("admin123", PASSWORD_BCRYPT);'
--

/*
INSERT INTO `users` (`username`, `display_name`, `password`, `role`) VALUES
('admin', 'Administrator', '$2y$10$...insert_bcrypt_hash_here...', 'admin');
*/
