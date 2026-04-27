<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Facades\Purifier;

class RichText implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $sanitized = Purifier::clean((string) $value, 'tiptap');
        $stripped = trim(strip_tags($sanitized));

        return $stripped === '' ? null : $sanitized;
    }
}
