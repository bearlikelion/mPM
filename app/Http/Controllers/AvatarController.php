<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AvatarController extends Controller
{
    public function default(string $initials): Response
    {
        $initials = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $initials), 0, 2));
        if ($initials === '') {
            $initials = '?';
        }

        $palette = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#10b981', '#0ea5e9'];
        $bg = $palette[crc32($initials) % count($palette)];

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
            .'<rect width="128" height="128" fill="%s"/>'
            .'<text x="50%%" y="50%%" dy=".35em" text-anchor="middle" fill="#ffffff" '
            .'font-family="system-ui, -apple-system, sans-serif" font-size="56" font-weight="600">%s</text>'
            .'</svg>',
            $bg,
            htmlspecialchars($initials, ENT_XML1)
        );

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
