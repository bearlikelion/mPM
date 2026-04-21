<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeZone;

class Timezones
{
    /**
     * @var array<string, string>|null
     */
    protected static ?array $options = null;

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        if (static::$options !== null) {
            return static::$options;
        }

        $options = [];

        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            $offset = CarbonImmutable::now($timezone)->format('P');
            $options[$timezone] = sprintf('%s (UTC%s)', str_replace('_', ' ', $timezone), $offset);
        }

        return static::$options = $options;
    }
}
