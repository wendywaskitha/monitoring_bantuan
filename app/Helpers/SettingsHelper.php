<?php
// app/Helpers/SettingsHelper.php (atau lokasi lain)

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

if (!function_exists('setting')) {
    /**
     * Mengambil nilai pengaturan dari database (dengan caching).
     *
     * @param string $key Kunci pengaturan.
     * @param mixed $default Nilai default jika kunci tidak ditemukan.
     * @return mixed
     */
    function setting(string $key, mixed $default = null): mixed
    {
        // Cache key unik
        $cacheKey = 'settings.' . $key;

        // Coba ambil dari cache dulu
        // Cache::forget($cacheKey); // Uncomment untuk clear cache saat testing
        return Cache::rememberForever($cacheKey, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }
}

if (!function_exists('setting_asset')) {
     /**
     * Mengambil URL asset dari pengaturan (logo/favicon).
     *
     * @param string $key Kunci pengaturan (misal: 'logo_path').
     * @param string|null $defaultUrl URL default jika tidak ada.
     * @return string|null
     */
    function setting_asset(string $key, ?string $defaultUrl = null): ?string
    {
        $path = setting($key);
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }
        return $defaultUrl ? asset($defaultUrl) : null; // Gunakan asset() untuk default
    }
}

if (!function_exists('clear_setting_cache')) {
    /**
     * Menghapus cache pengaturan tertentu atau semua.
     *
     * @param string|null $key Kunci spesifik atau null untuk semua.
     */
    function clear_setting_cache(?string $key = null): void
    {
        if ($key) {
            Cache::forget('settings.' . $key);
        } else {
            // Cara menghapus semua cache dengan prefix (memerlukan tag atau cara lain)
            // Cara sederhana: cari semua key di DB dan forget satu per satu
            Setting::pluck('key')->each(function ($dbKey) {
                Cache::forget('settings.' . $dbKey);
            });
            Log::info('All settings cache cleared.');
        }
    }
}
