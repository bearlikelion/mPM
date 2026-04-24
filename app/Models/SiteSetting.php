<?php

namespace App\Models;

use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_enabled',
        'org_creation_enabled',
        'org_invites_bypass_registration',
        'org_limit_per_user',
        'user_limit_per_org',
    ];

    protected function casts(): array
    {
        return [
            'registration_enabled' => 'boolean',
            'org_creation_enabled' => 'boolean',
            'org_invites_bypass_registration' => 'boolean',
            'org_limit_per_user' => 'integer',
            'user_limit_per_org' => 'integer',
        ];
    }

    public static function current(): self
    {
        $settings = static::first();

        if ($settings === null) {
            static::query()->insert(['created_at' => now(), 'updated_at' => now()]);
            $settings = static::first();
        }

        return $settings;
    }
}
