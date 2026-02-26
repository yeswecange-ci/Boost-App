<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Lire un paramètre — DB en priorité, fallback sur config/env.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", self::CACHE_TTL, function () use ($key, $default) {
            $row = Setting::find($key);
            if ($row !== null && $row->value !== null && $row->value !== '') {
                return $row->value;
            }
            // Fallback : config Laravel (qui lit lui-même depuis .env)
            return config('services.' . $key, $default);
        });
    }

    /**
     * Lire un booléen.
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::get($key);
        if ($value === null) return $default;
        return in_array($value, ['true', '1', 'yes', true, 1], true);
    }

    /**
     * Lire un entier.
     */
    public static function int(string $key, int $default = 0): int
    {
        return (int) (static::get($key) ?? $default);
    }

    /**
     * Écrire un ou plusieurs paramètres et invalider le cache.
     */
    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value]
        );
        Cache::forget("setting:{$key}");
    }

    /**
     * Écrire un tableau de paramètres en une fois.
     */
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }

    /**
     * Récupérer tous les paramètres d'un groupe sous forme de tableau associatif.
     */
    public static function group(string $group): array
    {
        return Setting::where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Invalider tout le cache settings (utile après un bulk update).
     */
    public static function clearCache(): void
    {
        $keys = Setting::pluck('key')->toArray();
        foreach ($keys as $key) {
            Cache::forget("setting:{$key}");
        }
    }
}
