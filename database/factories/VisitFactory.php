<?php

namespace Database\Factories;

use App\Enums\VisitStatus;
use App\Enums\VisitUnit;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'facility_id' => User::factory()->facility(),
            'title' => fake()->realText(30),
            'description' => fake()->realText(200),
            'venue' => fake()->city(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+2 hours'),
            'unit' => VisitUnit::Person,
            'status' => VisitStatus::Draft,
        ];
    }

    /**
     * 1組ずつ申し込む慰問（漫才のコンビなど）
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => ['unit' => VisitUnit::Group]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => VisitStatus::Draft]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['status' => VisitStatus::Published]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => ['status' => VisitStatus::Unpublished]);
    }

    /**
     * 開催済みの慰問
     */
    public function past(): static
    {
        $startsAt = fake()->dateTimeBetween('-3 months', '-1 week');

        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+2 hours'),
        ]);
    }
}
