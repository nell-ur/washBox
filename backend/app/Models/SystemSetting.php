<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Automatically casts the value when you access $setting->value
     */
    public function getValueAttribute($value)
{
    return match($this->type) {
        'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        'integer' => (int) $value,
        default => $value,
    };
}
    /**
     * Static helper to retrieve a value by key.
     * Uses a 5-minute cache to avoid repeated DB hits on every page load.
     */
    public static function get(string $key, $default = null)
    {
        $all = cache()->remember('system_settings', 300, function () {
            return static::all()->keyBy('key');
        });

        return isset($all[$key]) ? $all[$key]->value : $default;
    }

    /**
     * Static helper to create or update a setting.
     * Busts the cache so the next read reflects the change immediately.
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): void
    {
        $formattedValue = ($type === 'json') ? json_encode($value) : $value;

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $formattedValue,
                'type'  => $type,
                'group' => $group,
            ]
        );

        // Invalidate cache so subsequent reads are fresh
        cache()->forget('system_settings');
    }


}
