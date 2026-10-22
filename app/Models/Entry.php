<?php

namespace App\Models;

use App\Enums\EntryStatus;
use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $visit_id
 * @property EntryStatus $status
 * @property int $total_amount
 * @property string|null $stripe_checkout_session_id
 * @property string|null $payment_intent_id
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['visit_id', 'total_amount'])]
class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    /**
     * DB の既定値と同じ。作成直後のモデルでも status が null にならないようにする。
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => EntryStatus::Pending->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EntryStatus::class,
            'total_amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * 申し込み者（福祉施設ではない）
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
    public function items(): HasMany
    {
        return $this->hasMany(EntryItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === EntryStatus::Pending;
    }

    public function isPaid(): bool
    {
        return $this->status === EntryStatus::Paid;
    }

    /**
     * 支払い待ちのときだけキャンセルにする。支払い済みの申し込みは壊さない。
     * キャンセルにすると残り人数の計算から外れ、押さえていた枠が戻る。
     */
    public function cancelIfPending(): bool
    {
        $updated = static::query()
            ->whereKey($this->id)
            ->where('status', EntryStatus::Pending)
            ->update(['status' => EntryStatus::Cancelled]);

        $this->refresh();

        return $updated === 1;
    }
}
