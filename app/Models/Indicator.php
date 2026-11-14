<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Indicator extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'sgs_code',
        'name',
    ];

    protected static function booted(): void
    {
        static::updating(function (Indicator $indicator): void {
            if ($indicator->isDirty('sgs_code')) {
                throw new LogicException('O código SGS de um indicador não pode ser alterado.');
            }
        });
    }

    /**
     * @return HasMany<IndicatorValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(IndicatorValue::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sgs_code' => 'integer',
        ];
    }
}
