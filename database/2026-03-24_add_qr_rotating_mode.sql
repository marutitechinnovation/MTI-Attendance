-- ============================================================
--  QR: static (print) vs rotating (browser live) mode
--  Date: 2026-03-24
--  Mirrors: app/Database/Migrations/2026-03-24-000002_AddQrRotatingMode.php
-- ============================================================

ALTER TABLE `qr_tokens`
    ADD COLUMN `qr_mode` ENUM('static', 'rotating') NOT NULL DEFAULT 'static' AFTER `is_active`,
    ADD COLUMN `public_slug` VARCHAR(32) NULL DEFAULT NULL AFTER `qr_mode`,
    ADD COLUMN `last_token_rotated_at` DATETIME NULL DEFAULT NULL AFTER `public_slug`,
    ADD UNIQUE KEY `qr_tokens_public_slug_unique` (`public_slug`);

INSERT IGNORE INTO `settings` (`key`, `value`, `updated_at`) VALUES
    ('qr_rotating_interval_seconds', '15', NOW());

-- Rollback (manual):
-- ALTER TABLE `qr_tokens` DROP INDEX `qr_tokens_public_slug_unique`;
-- ALTER TABLE `qr_tokens` DROP COLUMN `last_token_rotated_at`;
-- ALTER TABLE `qr_tokens` DROP COLUMN `public_slug`;
-- ALTER TABLE `qr_tokens` DROP COLUMN `qr_mode`;
-- DELETE FROM `settings` WHERE `key` = 'qr_rotating_interval_seconds';
