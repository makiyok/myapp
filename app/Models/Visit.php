<?php

namespace App\Models;

use App\Enums\VisitStatus;
use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $facility_id
 * @property string $title
 * @property string $description
 * @property string $venue
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property VisitStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'description', 'venue', 'starts_at', 'ends_at'])]
class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => VisitStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facility_id');
    }

    /**
     * @return HasMany<VisitMenu, $this>
     */
    public function visitMenus(): HasMany
    {
        return $this->hasMany(VisitMenu::class);
    }

    /**
     * @return HasMany<Entry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * @param  Builder<Visit>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', VisitStatus::Published);
    }

    /**
     * 開催前（開始日時が現在以降）の慰問
     *
     * @param  Builder<Visit>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === VisitStatus::Published;
    }
}
