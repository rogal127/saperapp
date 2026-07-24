<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeenNotice extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'notice_key', 'seen_at'];

    protected function casts(): array
    {
        return [
            'seen_at' => 'datetime',
        ];
    }

    public static function shouldShow(int $userId, string $key): bool
    {
        return ! self::where('user_id', $userId)->where('notice_key', $key)->exists();
    }

    public static function markSeen(int $userId, string $key): void
    {
        self::firstOrCreate(
            ['user_id' => $userId, 'notice_key' => $key],
            ['seen_at' => now()],
        );
    }
}
