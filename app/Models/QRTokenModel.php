<?php

namespace App\Models;

use CodeIgniter\Model;

class QRTokenModel extends Model
{
    protected $table         = 'qr_tokens';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'token',
        'location_name',
        'latitude',
        'longitude',
        'geofence_radius',
        'is_active',
        'qr_mode',
        'public_slug',
        'last_token_rotated_at',
    ];
    protected $useTimestamps = true;

    public function getActive(): array
    {
        return $this->where('is_active', 1)->findAll();
    }

    public function findByToken(string $token): ?array
    {
        return $this->where('token', $token)->where('is_active', 1)->first();
    }

    public function findActiveRotatingBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $row = $this->where('public_slug', $slug)
            ->where('is_active', 1)
            ->where('qr_mode', 'rotating')
            ->first();

        return $row ?: null;
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function generateUniquePublicSlug(): string
    {
        for ($i = 0; $i < 12; $i++) {
            $slug = bin2hex(random_bytes(8));
            if (!$this->where('public_slug', $slug)->first()) {
                return $slug;
            }
        }

        return bin2hex(random_bytes(10));
    }

    public function getRotatingIntervalSeconds(?SettingsModel $settings = null): int
    {
        $settings ??= new SettingsModel();
        $v = (int) $settings->getSetting('qr_rotating_interval_seconds', 15);

        return max(5, min(600, $v));
    }

    /**
     * For rotating QRs, rotate token if the configured interval has elapsed.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    public function ensureFreshRotatingToken(array $row): array
    {
        if (($row['qr_mode'] ?? 'static') !== 'rotating') {
            return $row;
        }

        $interval = $this->getRotatingIntervalSeconds();
        $lastRaw  = $row['last_token_rotated_at'] ?? $row['created_at'] ?? date('Y-m-d H:i:s');
        $last     = strtotime((string) $lastRaw) ?: time();

        if (time() - $last >= $interval) {
            $this->rotateToken((int) $row['id']);
            $fresh = $this->find((int) $row['id']);

            return $fresh ?? $row;
        }

        return $row;
    }

    public function rotateToken(int $id): void
    {
        $new = $this->generateToken();
        $this->update($id, [
            'token'                 => $new,
            'last_token_rotated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
