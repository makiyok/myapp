<?php

namespace App\Models;

use App\Enums\EntryStatus;
use App\Services\EntryService;
use Database\Factories\VisitMenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $visit_id
 * @property string $name
 * @property int $price
 * @property int $capacity
 * @property Carbon $sales_starts_at
 * @property Carbon $sales_ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'price', 'capacity', 'sales_starts_at', 'sales_ends_at'])]
class VisitMenu extends Model
{
    /** @use HasFactory<VisitMenuFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'capacity' => 'integer',
            'sales_starts_at' => 'datetime',
            'sales_ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Visit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * @return HasMany<EntryItem, $this>
     */
    public function entryItems(): HasMany
    {
        return $this->hasMany(EntryItem::class);
    }

    /**
     * 販売期間内か（開始と終了の時刻ちょうども含む）
     */
    public function isOnSale(): bool
    {
        return now()->between($this->sales_starts_at, $this->sales_ends_at);
    }

    /**
     * 残り人数。定員から、支払い済みの人数と、有効な支払い待ちの人数を引く。
     */
    public function remaining(): int
    {
        $held = $this->entryItems()
            ->whereHas('entry', function (Builder $query): void {
                $query->where('status', EntryStatus::Paid)
                    ->orWhere(function (Builder $query): void {
                        $query->where('status', EntryStatus::Pending)
                            ->where('created_at', '>=', now()->subMinutes(EntryService::PENDING_MINUTES));
                    });
            })
            ->sum('quantity');

        return max(0, $this->capacity - (int) $held);
    }
}
