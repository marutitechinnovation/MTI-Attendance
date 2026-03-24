<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQrRotatingMode extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE `qr_tokens` ADD COLUMN `qr_mode` ENUM('static','rotating') NOT NULL DEFAULT 'static' AFTER `is_active`");
        $this->db->query('ALTER TABLE `qr_tokens` ADD COLUMN `public_slug` VARCHAR(32) NULL DEFAULT NULL AFTER `qr_mode`');
        $this->db->query('ALTER TABLE `qr_tokens` ADD COLUMN `last_token_rotated_at` DATETIME NULL DEFAULT NULL AFTER `public_slug`');
        $this->db->query('ALTER TABLE `qr_tokens` ADD UNIQUE KEY `qr_tokens_public_slug_unique` (`public_slug`)');

        if ($this->db->table('settings')->where('key', 'qr_rotating_interval_seconds')->countAllResults() === 0) {
            $this->db->table('settings')->insert([
                'key'         => 'qr_rotating_interval_seconds',
                'value'       => '15',
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE `qr_tokens` DROP INDEX `qr_tokens_public_slug_unique`');
        $this->db->query('ALTER TABLE `qr_tokens` DROP COLUMN `last_token_rotated_at`');
        $this->db->query('ALTER TABLE `qr_tokens` DROP COLUMN `public_slug`');
        $this->db->query('ALTER TABLE `qr_tokens` DROP COLUMN `qr_mode`');
        $this->db->table('settings')->where('key', 'qr_rotating_interval_seconds')->delete();
    }
}
