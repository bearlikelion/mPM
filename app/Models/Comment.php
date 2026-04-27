<?php

namespace App\Models;

use App\Casts\RichText;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Comment extends Model implements HasMedia
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'task_id',
        'user_id',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'body' => RichText::class,
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
