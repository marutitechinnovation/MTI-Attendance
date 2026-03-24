<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBrandingMapStatsSettings extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['key' => 'employee_app_name', 'value' => 'MTI Attendance'],
            ['key' => 'employee_app_short_name', 'value' => 'MTI Employee'],
            ['key' => 'employee_app_page_title', 'value' => 'Employee App'],
            ['key' => 'employee_app_description', 'value' => 'Employee attendance PWA.'],
            ['key' => 'employee_app_theme_color', 'value' => '#1A237E'],
            ['key' => 'employee_app_background_color', 'value' => '#F5F7FB'],
            ['key' => 'employee_app_accent_color', 'value' => '#00BCD4'],
            ['key' => 'map_tile_provider', 'value' => 'osm'],
            ['key' => 'map_tile_url', 'value' => ''],
            ['key' => 'map_tile_subdomains', 'value' => 'abc'],
            ['key' => 'map_attribution', 'value' => '© OpenStreetMap contributors'],
            ['key' => 'map_api_key', 'value' => ''],
            ['key' => 'stat_label_check_in', 'value' => 'Check In'],
            ['key' => 'stat_label_break_start', 'value' => 'Break Start'],
            ['key' => 'stat_label_break_end', 'value' => 'Break End'],
            ['key' => 'stat_label_check_out', 'value' => 'Check Out'],
            ['key' => 'stat_status_working', 'value' => 'Working'],
            ['key' => 'stat_status_on_break', 'value' => 'On Break'],
            ['key' => 'stat_status_complete', 'value' => 'Shift Complete'],
            ['key' => 'stat_status_not_in', 'value' => 'Not Checked In'],
        ];

        foreach ($defaults as $row) {
            $row['updated_at'] = date('Y-m-d H:i:s');
            if ($this->db->table('settings')->where('key', $row['key'])->countAllResults() === 0) {
                $this->db->table('settings')->insert($row);
            }
        }
    }

    public function down(): void
    {
        $keys = [
            'employee_app_name',
            'employee_app_short_name',
            'employee_app_page_title',
            'employee_app_description',
            'employee_app_theme_color',
            'employee_app_background_color',
            'employee_app_accent_color',
            'map_tile_provider',
            'map_tile_url',
            'map_tile_subdomains',
            'map_attribution',
            'map_api_key',
            'stat_label_check_in',
            'stat_label_break_start',
            'stat_label_break_end',
            'stat_label_check_out',
            'stat_status_working',
            'stat_status_on_break',
            'stat_status_complete',
            'stat_status_not_in',
        ];
        $this->db->table('settings')->whereIn('key', $keys)->delete();
    }
}
