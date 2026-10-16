<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_name', 'action', 'subject_type', 'subject_id',
        'description', 'properties', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Write an audit entry for the given subject.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $action, Model $subject, array $properties = []): self
    {
        $user = Auth::user();

        return static::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'description' => static::describe($action, $subject),
            'properties' => $properties ?: null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    protected static function describe(string $action, Model $subject): string
    {
        $label = $subject->name
            ?? $subject->number
            ?? $subject->email
            ?? ('#'.$subject->getKey());

        return sprintf(
            '%s %s "%s"',
            ucfirst(str_replace('_', ' ', $action)),
            class_basename($subject),
            $label,
        );
    }
}
