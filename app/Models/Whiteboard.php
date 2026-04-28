<?php

namespace App\Models;

use Database\Factories\WhiteboardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Whiteboard extends Model
{
    /** @use HasFactory<WhiteboardFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'updated_by',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
