<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A bookable unit, for example a restaurant table.
 */
class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use BelongsToCompany, HasFactory;

    /** @var list<string> */
    protected $fillable = ['company_id', 'branch_id', 'name', 'capacity'];

    /** @var array<string, string> */
    protected $casts = ['capacity' => 'integer'];

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
