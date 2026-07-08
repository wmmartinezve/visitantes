<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityAction;
use App\Enums\ActivityChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_role',
        'subject_type',
        'subject_id',
        'action',
        'channel',
        'client_id',
        'batch_id',
        'properties',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'action' => ActivityAction::class,
            'channel' => ActivityChannel::class,
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function subjectLabel(): string
    {
        $subject = $this->subject;

        if ($subject === null) {
            return class_basename((string) $this->subject_type).' #'.$this->subject_id;
        }

        return match (true) {
            $subject instanceof Invitado => $subject->nombreCompleto(),
            $subject instanceof Requerimiento => (string) $subject->item_solicitado,
            $subject instanceof Inventario => (string) $subject->item_nombre,
            $subject instanceof User => (string) $subject->name,
            default => class_basename($subject).' #'.$subject->getKey(),
        };
    }
}
