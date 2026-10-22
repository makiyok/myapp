<?php

namespace Database\Factories;

use App\Models\Visit;
use App\Models\VisitMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitMenu>
 */
class VisitMenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'name' => fake()->randomElement(['一般', '学生', '小学生以下', 'ペア']),
            'price' => fake()->numberBetween(0, 10) * 500,
            'capacity' => fake()->numberBetween(10, 100),
            'sales_starts_at' => now()->subWeek(),
            'sales_ends_at' => now()->addWeek(),
        ];
    }

    /**
     * 販売開始前のメニュー
     */
    public function beforeSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'sales_starts_at' => now()->addDay(),
            'sales_ends_at' => now()->addWeek(),
        ]);
    }

    /**
     * 販売終了後のメニュー
     */
    public function afterSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'sales_starts_at' => now()->subWeek(),
            'sales_ends_at' => now()->subDay(),
        ]);
    }
}
