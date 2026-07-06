<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineSyncRecord extends Model
{
    protected $primaryKey = 'client_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'client_id',
        'type',
        'server_id',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
