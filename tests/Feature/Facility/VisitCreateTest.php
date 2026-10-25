<?php

use App\Enums\VisitStatus;
use App\Enums\VisitUnit;
use App\Models\User;
use App\Models\Visit;
use Livewire\Livewire;

function validVisitInput(array $overrides = []): array
{
    return array_merge([
        'title' => 'テスト慰問',
        'description' => '説明文です。',
        'venue' => 'テスト会場',
        'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
        'ends_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        'unit' => 'person',
    ], $overrides);
}

function fillVisitForm($component, array $input)
{
    foreach ($input as $key => $value) {
        $component->set("form.$key", $value);
    }

    return $component;
}

test('an facility can create an visit as a draft', function () {
    $facility = User::factory()->facility()->create();

    $component = Livewire::actingAs($facility)->test('pages::facility.visits.create');
    fillVisitForm($component, validVisitInput())->call('save')->assertHasNoErrors();

    $visit = Visit::sole();
    expect($visit->title)->toBe('テスト慰問')
        ->and($visit->status)->toBe(VisitStatus::Draft)
        ->and($visit->facility_id)->toBe($facility->id);

    $component->assertRedirect(route('facility.visits.edit', $visit));
});

test('a facility can choose to count entries in groups', function () {
    $facility = User::factory()->facility()->create();

    $component = Livewire::actingAs($facility)->test('pages::facility.visits.create');
    fillVisitForm($component, validVisitInput(['unit' => 'group']))->call('save')->assertHasNoErrors();

    expect(Visit::sole()->unit)->toBe(VisitUnit::Group);
});

test('the unit must be people or groups', function () {
    $facility = User::factory()->facility()->create();

    $component = Livewire::actingAs($facility)->test('pages::facility.visits.create');
    fillVisitForm($component, validVisitInput(['unit' => 'family']))
        ->call('save')
        ->assertHasErrors(['form.unit']);

    expect(Visit::count())->toBe(0);
});

test('all fields are required', function (string $field) {
    $facility = User::factory()->facility()->create();

    $component = Livewire::actingAs($facility)->test('pages::facility.visits.create');
    fillVisitForm($component, validVisitInput([$field => '']))
        ->call('save')
        ->assertHasErrors(["form.$field" => 'required']);

    expect(Visit::count())->toBe(0);
})->with(['title', 'description', 'venue', 'starts_at', 'ends_at', 'unit']);

test('title and venue are limited to 100 characters', function (string $field) {
    $facility = User::factory()->facility()->create();

    $component = Livewire::actingAs($facility)->test('pages::facility.visits.create');
    fillVisitForm($component, validVisitInput([$field => str_repeat('あ', 101)]))
        ->call('save')
        ->assertHasErrors(["form.$field" => 'max']);
    expect(Visit::count())->toBe(0);

    $component->set("form.$field", str_repeat('あ', 100))
        ->call('save')
        ->assertHasNoErrors();
    expect(Visit::count())->toBe(1);
})->with(['title', 'venue']);

test('ends_at must be after starts_at', function (string $endsAt) {
    $facility = User::factory()->facility()->create();
    $startsAt = now()->addDays(2)->format('Y-m-d\TH:i');

    $component = Livewire::actingAs($facility)->test('pages::facility.visits.create');
    fillVisitForm($component, validVisitInput(['starts_at' => $startsAt, 'ends_at' => $endsAt]))
        ->call('save')
        ->assertHasErrors(['form.ends_at']);

    expect(Visit::count())->toBe(0);
})->with([
    'before' => fn () => now()->addDay()->format('Y-m-d\TH:i'),
    'same time' => fn () => now()->addDays(2)->format('Y-m-d\TH:i'),
]);

test('visitors cannot open the create page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('facility.visits.create'))
        ->assertForbidden();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::facility.visits.create')
        ->assertForbidden();
});

test('save re-checks authorization after the page was opened', function () {
    $facility = User::factory()->facility()->create();

    $component = Livewire::actingAs($facility)->test('pages::facility.visits.create');
    fillVisitForm($component, validVisitInput());

    $facility->forceFill(['role' => 'visitor'])->save();

    $component->call('save')->assertForbidden();

    expect(Visit::count())->toBe(0);
});

test('dates must use the datetime-local format', function (string $field, string $value) {
    $facility = User::factory()->facility()->create();

    $component = Livewire::actingAs($facility)->test('pages::facility.visits.create');
    fillVisitForm($component, validVisitInput([$field => $value]))
        ->call('save')
        ->assertHasErrors(["form.$field" => 'date_format']);

    expect(Visit::count())->toBe(0);
})->with(['starts_at', 'ends_at'])->with([
    'relative word' => 'tomorrow',
    'with timezone offset' => '2099-01-01T10:00+00:00',
    'date only' => '2099-01-01',
]);

test('description is limited to 5000 characters', function () {
    $facility = User::factory()->facility()->create();

    $component = Livewire::actingAs($facility)->test('pages::facility.visits.create');
    fillVisitForm($component, validVisitInput(['description' => str_repeat('あ', 5001)]))
        ->call('save')
        ->assertHasErrors(['form.description' => 'max']);
    expect(Visit::count())->toBe(0);

    $component->set('form.description', str_repeat('あ', 5000))
        ->call('save')
        ->assertHasNoErrors();
    expect(Visit::count())->toBe(1);
});
