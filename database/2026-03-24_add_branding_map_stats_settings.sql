-- ============================================================
--  Migration: Branding, map tiles, and PWA stat labels (settings)
--  Date: 2026-03-24
--  Mirrors: app/Database/Migrations/2026-03-24-000001_AddBrandingMapStatsSettings.php
--
--  Inserts default rows into `settings` only when each `key` is absent
--  (requires UNIQUE on `key`; duplicate keys are skipped).
-- ============================================================

INSERT IGNORE INTO `settings` (`key`, `value`, `updated_at`) VALUES
    ('employee_app_name', 'MTI Attendance', NOW()),
    ('employee_app_short_name', 'MTI Employee', NOW()),
    ('employee_app_page_title', 'Employee App', NOW()),
    ('employee_app_description', 'Employee attendance PWA.', NOW()),
    ('employee_app_theme_color', '#1A237E', NOW()),
    ('employee_app_background_color', '#F5F7FB', NOW()),
    ('employee_app_accent_color', '#00BCD4', NOW()),
    ('map_tile_provider', 'osm', NOW()),
    ('map_tile_url', '', NOW()),
    ('map_tile_subdomains', 'abc', NOW()),
    ('map_attribution', '© OpenStreetMap contributors', NOW()),
    ('map_api_key', '', NOW()),
    ('stat_label_check_in', 'Check In', NOW()),
    ('stat_label_break_start', 'Break Start', NOW()),
    ('stat_label_break_end', 'Break End', NOW()),
    ('stat_label_check_out', 'Check Out', NOW()),
    ('stat_status_working', 'Working', NOW()),
    ('stat_status_on_break', 'On Break', NOW()),
    ('stat_status_complete', 'Shift Complete', NOW()),
    ('stat_status_not_in', 'Not Checked In', NOW());

-- ============================================================
--  Rollback (optional): remove only these keys
-- ============================================================
-- DELETE FROM `settings` WHERE `key` IN (
--     'employee_app_name',
--     'employee_app_short_name',
--     'employee_app_page_title',
--     'employee_app_description',
--     'employee_app_theme_color',
--     'employee_app_background_color',
--     'employee_app_accent_color',
--     'map_tile_provider',
--     'map_tile_url',
--     'map_tile_subdomains',
--     'map_attribution',
--     'map_api_key',
--     'stat_label_check_in',
--     'stat_label_break_start',
--     'stat_label_break_end',
--     'stat_label_check_out',
--     'stat_status_working',
--     'stat_status_on_break',
--     'stat_status_complete',
--     'stat_status_not_in'
-- );
