<?php

use App\Enums\VisitStatus;
use App\Enums\VisitUnit;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitMenu;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->facility()->create();
    $this->visit = Visit::factory()->for($this->owner, 'facility')->create();
});

test('the owner can update the visit', function () {
    $startsAt = now()->addDays(5)->format('Y-m-d\TH:i');
    $endsAt = now()->addDays(6)->format('Y-m-d\TH:i');

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->assertSet('form.title', $this->visit->title)
        ->assertSet('form.unit', 'person')
        ->set('form.title', '更新後のタイトル')
        ->set('form.unit', 'group')
        ->set('form.starts_at', $startsAt)
        ->set('form.ends_at', $endsAt)
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $this->visit->fresh();
    expect($fresh->title)->toBe('更新後のタイトル')
        ->and($fresh->unit)->toBe(VisitUnit::Group)
        ->and($fresh->starts_at->format('Y-m-d\TH:i'))->toBe($startsAt)
        ->and($fresh->facility_id)->toBe($this->owner->id);
});

test('the owner can toggle between draft and published', function () {
    VisitMenu::factory()->for($this->visit)->create();

    $component = Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->assertSee('公開する');

    $component->call('togglePublish');
    expect($this->visit->fresh()->status)->toBe(VisitStatus::Published);

    $component->assertSee('下書きに戻す')->call('togglePublish');
    expect($this->visit->fresh()->status)->toBe(VisitStatus::Draft);
});

test('the owner can delete the visit', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->call('deleteVisit')
        ->assertRedirect(route('facility.visits.index'));

    expect(Visit::count())->toBe(0);
});

test('other facilities cannot open the edit page', function () {
    $other = User::factory()->facility()->create();

    $this->actingAs($other)
        ->get(route('facility.visits.edit', $this->visit))
        ->assertForbidden();

    Livewire::actingAs($other)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->assertForbidden();
});

test('validation errors are shown on update', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->set('form.title', '')
        ->set('form.venue', str_repeat('あ', 101))
        ->set('form.ends_at', now()->subDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['form.title' => 'required', 'form.venue' => 'max', 'form.ends_at']);

    expect($this->visit->fresh()->title)->toBe($this->visit->title);
});

test('methods re-check authorization after ownership changes', function (string $method) {
    $other = User::factory()->facility()->create();

    $component = Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->set('form.title', '乗っ取りタイトル');

    $this->visit->forceFill(['facility_id' => $other->id])->save();

    $component->call($method)->assertForbidden();

    $fresh = $this->visit->fresh();
    expect($fresh)->not->toBeNull()
        ->and($fresh->title)->toBe($this->visit->title)
        ->and($fresh->status)->toBe(VisitStatus::Draft)
        ->and($fresh->facility_id)->toBe($other->id);
})->with(['save', 'togglePublish', 'deleteVisit']);

test('an unpublished visit cannot be toggled by the owner', function () {
    $visit = Visit::factory()->for($this->owner, 'facility')->unpublished()->create();

    $component = Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $visit])
        ->assertDontSee('公開する')
        ->assertDontSee('下書きに戻す')
        ->assertDontSeeHtml('data-test="toggle-publish-button"');

    $component->call('togglePublish')->assertForbidden();

    expect($visit->fresh()->status)->toBe(VisitStatus::Unpublished);
});
