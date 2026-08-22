<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value_encrypted', 'updated_by'];

    /**
     * Read a setting saved by an admin, falling back to an env var of the
     * same name (uppercased) if nothing has been saved yet, then to
     * $default. This is the ONLY place secrets should be decrypted --
     * everywhere else in the app should call the setting() helper.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            $cached = Cache::remember("setting:{$key}", now()->addMinutes(10), function () use ($key) {
                $row = static::where('key', $key)->first();

                if (! $row || $row->value_encrypted === null) {
                    return null;
                }

                try {
                    return Crypt::decryptString($row->value_encrypted);
                } catch (\Throwable $e) {
                    return null;
                }
            });
        } catch (\Throwable $e) {
            $cached = null;
        }

        if ($cached !== null && $cached !== '') {
            return $cached;
        }

        return env(strtoupper($key), $default);
    }

    public static function putValue(string $key, ?string $value, ?int $updatedBy = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value_encrypted' => $value === null || $value === '' ? null : Crypt::encryptString($value),
                'updated_by' => $updatedBy,
            ]
        );

        Cache::forget("setting:{$key}");
    }

    public static function isConfigured(string $key): bool
    {
        $row = static::where('key', $key)->first();

        return $row && $row->value_encrypted !== null;
    }
}
