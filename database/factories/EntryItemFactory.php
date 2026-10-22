<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\VisitMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntryItem>
 */
class EntryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_id' => Entry::factory(),
            'visit_menu_id' => VisitMenu::factory(),
            'quantity' => fake()->numberBetween(1, 4),
            'unit_price' => fake()->numberBetween(0, 10) * 500,
        ];
    }
}
