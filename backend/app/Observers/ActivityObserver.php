<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityObserver
{
    public function created(Model $model): void
    {
        ActivityLog::record('created', $model, [
            'attributes' => $this->filter($model, $model->getAttributes()),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = [];

        foreach ($model->getChanges() as $key => $value) {
            if ($this->isIgnored($model, $key)) {
                continue;
            }

            $changes[$key] = [
                'from' => $model->getOriginal($key),
                'to' => $value,
            ];
        }

        if ($changes !== []) {
            ActivityLog::record('updated', $model, ['changes' => $changes]);
        }
    }

    public function deleted(Model $model): void
    {
        ActivityLog::record('deleted', $model);
    }

    public function restored(Model $model): void
    {
        ActivityLog::record('restored', $model);
    }

    public function forceDeleted(Model $model): void
    {
        ActivityLog::record('force_deleted', $model);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function filter(Model $model, array $attributes): array
    {
        return array_filter(
            $attributes,
            fn ($key) => ! $this->isIgnored($model, $key),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function isIgnored(Model $model, string $key): bool
    {
        $ignored = array_merge(
            $model->getHidden(),
            ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'],
        );

        return in_array($key, $ignored, true);
    }
}
