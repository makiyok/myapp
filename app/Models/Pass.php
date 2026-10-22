<?php

namespace App\Models;

use Database\Factories\PassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $entry_item_id
 * @property string $code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code'])]
class Pass extends Model
{
    /** @use HasFactory<PassFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<EntryItem, $this>
     */
    public function entryItem(): BelongsTo
    {
        return $this->belongsTo(EntryItem::class);
    }
}
