<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class ExternalIntegrationService
{
    public function getWeather(float $latitude, float $longitude): array
    {
        $url = config('cargotrace.external_apis.open_meteo_url');
        $timeout = (int) config('cargotrace.external_apis.timeout_seconds', 5);

        try {
            $response = Http::timeout($timeout)->get($url, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'current_weather' => 'true',
            ]);

            if (! $response->successful()) {
                return $this->fallbackWeather($latitude, $longitude, 'Weather provider returned non-200 status.');
            }

            $current = $response->json('current_weather');
            if (! is_array($current) || empty($current)) {
                return $this->fallbackWeather($latitude, $longitude, 'Weather payload was empty.');
            }

            return [
                'available' => true,
                'source' => 'Open-Meteo',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'temperature_c' => $current['temperature'] ?? null,
                'windspeed_kmh' => $current['windspeed'] ?? null,
                'weather_code' => $current['weathercode'] ?? null,
                'condition' => $this->describeWeatherCode((int) ($current['weathercode'] ?? 0)),
                'observed_at' => $current['time'] ?? now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            return $this->fallbackWeather($latitude, $longitude, 'Weather service unavailable or timed out.');
        }
    }

    public function geocodeAddress(string $query): array
    {
        $baseUrl = rtrim((string) config('cargotrace.external_apis.nominatim_url'), '/');
        $timeout = (int) config('cargotrace.external_apis.timeout_seconds', 5);
        $userAgent = (string) config('cargotrace.external_apis.nominatim_user_agent');

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['User-Agent' => $userAgent])
                ->get("{$baseUrl}/search", [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 3,
                ]);

            if (! $response->successful() || empty($response->json())) {
                return [
                    'available' => false,
                    'source' => 'Nominatim OpenStreetMap',
                    'results' => [],
                    'message' => 'No geocoding results returned.',
                ];
            }

            $results = collect($response->json())->map(fn ($item) => [
                'display_name' => $item['display_name'] ?? $query,
                'latitude' => isset($item['lat']) ? (float) $item['lat'] : null,
                'longitude' => isset($item['lon']) ? (float) $item['lon'] : null,
            ])->values()->all();

            return [
                'available' => true,
                'source' => 'Nominatim OpenStreetMap',
                'results' => $results,
            ];
        } catch (Throwable $e) {
            return [
                'available' => false,
                'source' => 'Nominatim OpenStreetMap',
                'results' => [],
                'message' => 'Geocoding service unavailable or timed out.',
            ];
        }
    }

    public function reverseGeocode(float $latitude, float $longitude): array
    {
        $baseUrl = rtrim((string) config('cargotrace.external_apis.nominatim_url'), '/');
        $timeout = (int) config('cargotrace.external_apis.timeout_seconds', 5);
        $userAgent = (string) config('cargotrace.external_apis.nominatim_user_agent');

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['User-Agent' => $userAgent])
                ->get("{$baseUrl}/reverse", [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json',
                ]);

            if (! $response->successful() || empty($response->json('display_name'))) {
                return [
                    'available' => false,
                    'source' => 'Nominatim OpenStreetMap',
                    'display_name' => sprintf('Coordinates %.4f, %.4f', $latitude, $longitude),
                ];
            }

            return [
                'available' => true,
                'source' => 'Nominatim OpenStreetMap',
                'display_name' => $response->json('display_name'),
                'address' => $response->json('address', []),
            ];
        } catch (Throwable $e) {
            return [
                'available' => false,
                'source' => 'Nominatim OpenStreetMap',
                'display_name' => sprintf('Coordinates %.4f, %.4f', $latitude, $longitude),
            ];
        }
    }

    protected function fallbackWeather(float $latitude, float $longitude, string $note): array
    {
        return [
            'available' => false,
            'source' => 'Fallback (Offline/Timeout)',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'temperature_c' => null,
            'windspeed_kmh' => null,
            'weather_code' => null,
            'condition' => 'Weather data unavailable',
            'note' => $note,
            'observed_at' => now()->toIso8601String(),
        ];
    }

    protected function describeWeatherCode(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear Sky',
            in_array($code, [1, 2, 3], true) => 'Partly Cloudy',
            in_array($code, [45, 48], true) => 'Foggy',
            in_array($code, [51, 53, 55, 61, 63, 65], true) => 'Rain',
            in_array($code, [71, 73, 75], true) => 'Snow',
            in_array($code, [80, 81, 82], true) => 'Rain Showers',
            $code >= 95 => 'Thunderstorm',
            default => 'Variable Conditions',
        };
    }
}
