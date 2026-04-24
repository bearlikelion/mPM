<?php

namespace App\Models;

use Database\Factories\SiteInviteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SiteInvite extends Model
{
    /** @use HasFactory<SiteInviteFactory> */
    use HasFactory;

    protected $fillable = [
        'token',
        'label',
        'max_uses',
        'used_count',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'max_uses' => 'integer',
            'used_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SiteInvite $invite) {
            $invite->token ??= Str::random(48);
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isExhausted();
    }

    public function consume(): void
    {
        $this->increment('used_count');
    }

    public function url(): string
    {
        return route('register', ['invite' => $this->token]);
    }

    public static function findValidByToken(?string $token): ?self
    {
        if (! $token) {
            return null;
        }

        $invite = static::where('token', $token)->first();

        return $invite && $invite->isValid() ? $invite : null;
    }
}
