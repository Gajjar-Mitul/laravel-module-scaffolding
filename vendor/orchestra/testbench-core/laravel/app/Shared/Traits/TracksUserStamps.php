<?php

namespace App\Shared\Traits;

use Illuminate\Support\Facades\Auth;

trait TracksUserStamps
{
    public static function bootTracksUserStamps(): void
    {
        static::creating(static function (self $model): void {
            if (Auth::check()) {
                if ($model->isFillable('created_by')) {
                    $model->created_by = Auth::id();
                }
                if ($model->isFillable('updated_by')) {
                    $model->updated_by = Auth::id();
                }
            }
        });

        static::updating(static function (self $model): void {
            if (Auth::check() && $model->isFillable('updated_by')) {
                $model->updated_by = Auth::id();
            }
        });
    }
}