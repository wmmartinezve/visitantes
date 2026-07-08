<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ActivityChannel;
use Illuminate\Support\Str;

final class ActivityLogContext
{
    private static ?ActivityChannel $channel = null;

    private static ?string $batchId = null;

    private static ?string $clientId = null;

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function using(ActivityChannel $channel, callable $callback, ?string $batchId = null): mixed
    {
        $previousChannel = self::$channel;
        $previousBatchId = self::$batchId;

        self::$channel = $channel;
        self::$batchId = $batchId ?? self::$batchId ?? (string) Str::uuid();

        try {
            return $callback();
        } finally {
            self::$channel = $previousChannel;
            self::$batchId = $previousBatchId;
        }
    }

    public static function setClientId(?string $clientId): void
    {
        self::$clientId = $clientId;
    }

    public static function clientId(): ?string
    {
        return self::$clientId;
    }

    public static function batchId(): ?string
    {
        return self::$batchId;
    }

    public static function channel(): ActivityChannel
    {
        if (self::$channel !== null) {
            return self::$channel;
        }

        if (! app()->runningInConsole() && request()) {
            $path = request()->path();

            if (str_starts_with($path, 'api/mobile')) {
                return ActivityChannel::MobileApi;
            }

            if (str_starts_with($path, 'api/offline')) {
                return ActivityChannel::OfflineSync;
            }

            if (str_starts_with($path, 'admin')) {
                return ActivityChannel::Admin;
            }

            if (str_starts_with($path, 'anfitrion')) {
                return ActivityChannel::LivewireAnfitrion;
            }

            if (str_starts_with($path, 'acopio')) {
                return ActivityChannel::LivewireAcopio;
            }
        }

        return ActivityChannel::System;
    }
}
