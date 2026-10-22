<?php

namespace Database\Factories;

use App\Enums\EntryStatus;
use App\Models\Entry;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'visit_id' => Visit::factory()->published(),
            'status' => EntryStatus::Pending,
            'total_amount' => 0,
            'stripe_checkout_session_id' => null,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EntryStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => EntryStatus::Cancelled]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EntryStatus::Refunded,
            'paid_at' => now()->subDay(),
        ]);
    }
}
