<?php

namespace App\Controllers;

use App\Models\QRTokenModel;
use App\Models\AttendanceModel;
use App\Models\SettingsModel;

class MapView extends BaseController
{
    public function index()
    {
        $qrModel    = new QRTokenModel();
        $attModel   = new AttendanceModel();
        $settings   = new SettingsModel()->getAll();

        $tiles = $this->resolveMapTiles($settings);

        return view('map/index', [
            'locations'       => json_encode($qrModel->getActive()),
            'liveData'        => json_encode($attModel->getLiveCheckedIn()),
            'pageTitle'       => 'Live Map',
            'mapTileUrl'      => $tiles['url'],
            'mapAttribution'  => $tiles['attribution'],
            'mapSubdomains'   => $tiles['subdomains'],
        ]);
    }

    /**
     * @param array<string, string> $settings
     *
     * @return array{url: string, attribution: string, subdomains: string}
     */
    private function resolveMapTiles(array $settings): array
    {
        $provider = $settings['map_tile_provider'] ?? 'osm';
        $apiKey   = $settings['map_api_key'] ?? '';
        $custom   = trim((string) ($settings['map_tile_url'] ?? ''));

        if ($provider === 'custom' && $custom !== '') {
            $url = str_replace(['{apikey}', '{API_KEY}'], $apiKey, $custom);

            return [
                'url'          => $url,
                'attribution'  => (string) ($settings['map_attribution'] ?? ''),
                'subdomains'   => (string) ($settings['map_tile_subdomains'] ?? 'abc'),
            ];
        }

        return [
            'url'          => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution'  => '© OpenStreetMap contributors',
            'subdomains'   => 'abc',
        ];
    }
}
