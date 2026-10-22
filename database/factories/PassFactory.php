<?php

namespace Database\Factories;

use App\Models\EntryItem;
use App\Models\Pass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pass>
 */
class PassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_item_id' => EntryItem::factory(),
            'code' => (string) Str::uuid(),
        ];
    }
}
