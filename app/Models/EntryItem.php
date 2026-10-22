<?php

namespace App\Models;

use Database\Factories\EntryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $entry_id
 * @property int $visit_menu_id
 * @property int $quantity
 * @property int $unit_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['visit_menu_id', 'quantity', 'unit_price'])]
class EntryItem extends Model
{
    /** @use HasFactory<EntryItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Entry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    /**
     * @return BelongsTo<VisitMenu, $this>
     */
    public function visitMenu(): BelongsTo
    {
        return $this->belongsTo(VisitMenu::class);
    }

    /**
     * @return HasMany<Pass, $this>
     */
    public function passes(): HasMany
    {
        return $this->hasMany(Pass::class);
    }
}
