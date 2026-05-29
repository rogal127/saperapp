<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finding extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'latitude',
        'longitude',
        'depth_cm',
        'photo_path',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'depth_cm' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function withinRadiusCollection(float $lat, float $lng, int $radiusKm)
    {
        return static::with('user:id,name')
            ->get()
            ->map(function ($finding) use ($lat, $lng) {
                $finding->distance = self::haversine($lat, $lng, $finding->latitude, $finding->longitude);
                return $finding;
            })
            ->filter(fn($f) => $f->distance <= $radiusKm)
            ->sortBy('distance')
            ->values();
    }

    private static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
