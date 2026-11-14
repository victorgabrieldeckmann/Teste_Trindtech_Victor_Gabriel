<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indicator extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'sgs_code',
        'name',
    ];

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
