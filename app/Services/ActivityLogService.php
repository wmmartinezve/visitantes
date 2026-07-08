<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\ActivityChannel;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        ActivityAction $action,
        Model $subject,
        ?string $description = null,
        array $properties = [],
        ?User $actor = null,
        ?ActivityChannel $channel = null,
    ): ActivityLog {
        $actor ??= Auth::user();

        return ActivityLog::query()->create([
            'user_id' => $actor?->id,
            'user_role' => $actor?->rol?->value,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'channel' => $channel ?? ActivityLogContext::channel(),
            'client_id' => ActivityLogContext::clientId(),
            'batch_id' => ActivityLogContext::batchId(),
            'properties' => $properties === [] ? null : $properties,
            'description' => $description,
        ]);
    }

    public function created(Model $subject, ?string $description = null, ?ActivityChannel $channel = null): ActivityLog
    {
        return $this->log(
            ActivityAction::Created,
            $subject,
            $description ?? 'Registro creado',
            ['new' => $this->snapshot($subject)],
            channel: $channel,
        );
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function updated(
        Model $subject,
        array $old,
        array $new,
        ?string $description = null,
        ?ActivityChannel $channel = null,
    ): ActivityLog {
        return $this->log(
            ActivityAction::Updated,
            $subject,
            $description ?? 'Registro actualizado',
            ['old' => $old, 'new' => $new],
            channel: $channel,
        );
    }

    public function deleted(Model $subject, bool $force = false, ?ActivityChannel $channel = null): ActivityLog
    {
        return $this->log(
            $force ? ActivityAction::ForceDeleted : ActivityAction::Deleted,
            $subject,
            $force ? 'Registro eliminado permanentemente' : 'Registro eliminado',
            ['old' => $this->snapshot($subject)],
            channel: $channel,
        );
    }

    public function restored(Model $subject, ?ActivityChannel $channel = null): ActivityLog
    {
        return $this->log(
            ActivityAction::Restored,
            $subject,
            'Registro restaurado',
            ['new' => $this->snapshot($subject)],
            channel: $channel,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Model $model): array
    {
        $attributes = $model->attributesToArray();

        unset($attributes['password'], $attributes['remember_token']);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{old: array<string, mixed>, new: array<string, mixed>}
     */
    public function diff(array $before, array $after): array
    {
        $old = [];
        $new = [];

        foreach ($after as $key => $value) {
            if (in_array($key, ['updated_at', 'created_at', 'password', 'remember_token'], true)) {
                continue;
            }

            $previous = $before[$key] ?? null;

            if ($previous != $value) {
                $old[$key] = $previous;
                $new[$key] = $value;
            }
        }

        return ['old' => $old, 'new' => $new];
    }
}
