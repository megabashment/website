-- =====================================================================
-- MIGRATION 001: Multi-Plan + Tier-System
-- =====================================================================
--
-- Run via phpMyAdmin — einmalig ausführen.
-- Bestehende Daten bleiben vollständig erhalten.
--
-- Was sich ändert:
--   1. users bekommt tier-Spalte (free / premium)
--   2. Neue Tabelle week_plans (benannte Wochenpläne pro User)
--   3. Neue Tabelle plan_shares (Teilen mit anderen Usern)
--   4. week_plan_entries bekommt plan_id (user_id bleibt vorerst als Fallback)
--
-- ─────────────────────────────────────────────────────────────────────

-- 1. Tier-Spalte zu users hinzufügen
ALTER TABLE users
  ADD COLUMN `tier` ENUM('free','premium') NOT NULL DEFAULT 'free' AFTER `role`;

-- ─────────────────────────────────────────────────────────────────────

-- 2. Tabelle week_plans anlegen
CREATE TABLE `week_plans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(128) NOT NULL DEFAULT 'Mein Wochenplan',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────

-- 3. Plan für jeden User mit bestehenden Einträgen anlegen
INSERT INTO `week_plans` (`owner_id`, `name`)
SELECT DISTINCT `user_id`, 'Mein Wochenplan'
FROM `week_plan_entries`;

-- Plan für User ohne Einträge anlegen
INSERT INTO `week_plans` (`owner_id`, `name`)
SELECT `id`, 'Mein Wochenplan'
FROM `users`
WHERE `id` NOT IN (SELECT `owner_id` FROM `week_plans`);

-- ─────────────────────────────────────────────────────────────────────

-- 4. plan_id Spalte zu week_plan_entries hinzufügen
ALTER TABLE `week_plan_entries`
  ADD COLUMN `plan_id` INT UNSIGNED NULL AFTER `id`;

-- ─────────────────────────────────────────────────────────────────────

-- 5. plan_id für alle bestehenden Einträge setzen
UPDATE `week_plan_entries` wpe
INNER JOIN `week_plans` wp ON wp.`owner_id` = wpe.`user_id`
SET wpe.`plan_id` = wp.`id`;

-- ─────────────────────────────────────────────────────────────────────

-- 6. Neuen Unique-Key für plan+tag anlegen
ALTER TABLE `week_plan_entries`
  ADD UNIQUE KEY `uq_plan_day` (`plan_id`, `day_index`);

-- ─────────────────────────────────────────────────────────────────────

-- 7. Tabelle plan_shares anlegen (für Premium-Sharing)
CREATE TABLE `plan_shares` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `plan_id` INT UNSIGNED NOT NULL,
  `shared_with_user_id` INT UNSIGNED NOT NULL,
  `permission` ENUM('view','edit') NOT NULL DEFAULT 'view',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_share` (`plan_id`, `shared_with_user_id`),
  FOREIGN KEY (`plan_id`) REFERENCES `week_plans`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shared_with_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Fertig. Zur Kontrolle:
--   SELECT * FROM week_plans;       -- sollte 1 Zeile pro User zeigen
--   SELECT plan_id, COUNT(*) FROM week_plan_entries GROUP BY plan_id;
-- ─────────────────────────────────────────────────────────────────────
