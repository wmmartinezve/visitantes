<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Enums\ActivityChannel;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait LogsFilamentRecordActivity
{
    /** @var array<string, mixed> */
    protected array $activityLogBeforeSave = [];

    protected function captureActivityBeforeSave(Model $record): void
    {
        $this->activityLogBeforeSave = app(ActivityLogService::class)->snapshot($record);
    }

    protected function logFilamentCreated(Model $record): void
    {
        app(ActivityLogService::class)->created(
            $record,
            channel: ActivityChannel::Admin,
        );
    }

    protected function logFilamentUpdated(Model $record): void
    {
        if ($this->activityLogBeforeSave === []) {
            return;
        }

        $logger = app(ActivityLogService::class);
        $diff = $logger->diff($this->activityLogBeforeSave, $logger->snapshot($record));

        if ($diff['old'] === []) {
            return;
        }

        $logger->updated(
            $record,
            $diff['old'],
            $diff['new'],
            channel: ActivityChannel::Admin,
        );
    }

    protected function logFilamentDeleted(Model $record, bool $force = false): void
    {
        app(ActivityLogService::class)->deleted(
            $record,
            force: $force,
            channel: ActivityChannel::Admin,
        );
    }

    protected function logFilamentRestored(Model $record): void
    {
        app(ActivityLogService::class)->restored(
            $record,
            channel: ActivityChannel::Admin,
        );
    }

    protected function usesSoftDeletes(Model $record): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($record), true);
    }
}
