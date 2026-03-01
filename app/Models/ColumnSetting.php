<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColumnSetting extends Model
{
    protected $table = 'column_settings';

    protected $fillable = [
        'page',
        'settings',
        'updated_by',
    ];

    /**
     * Cast settings to array automatically
     */
    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Get settings for a specific page
     */
    public static function getForPage(string $page): ?array
    {
        $setting = self::where('page', $page)->first();

        if (! $setting) {
            return null;
        }

        return $setting->settings;
    }

    /**
     * Save settings for a specific page
     */
    public static function saveForPage(string $page, array $settings, ?int $userId = null): self
    {
        return self::updateOrCreate(
            ['page' => $page],
            [
                'settings' => $settings,
                'updated_by' => $userId,
            ]
        );
    }

    /**
     * Get the user who last updated the settings
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
