<?php

use App\Enums\VisitStatus;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitMenu;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/**
 * メニューフォームの正しい入力値。引数で一部を上書きできる。
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validVisitMenuInput(array $overrides = []): array
{
    return array_merge([
        'name' => '一般',
        'price' => 3000,
        'capacity' => 50,
        'sales_starts_at' => '2030-01-01T10:00',
        'sales_ends_at' => '2030-01-31T18:00',
    ], $overrides);
}

/**
 * メニューフォームに値を入れる。
 *
 * @param  array<string, mixed>  $input
 */
function fillVisitMenuForm(mixed $component, array $input): mixed
{
    foreach ($input as $key => $value) {
        $component->set("visitMenuForm.{$key}", $value);
    }

    return $component;
}

beforeEach(function () {
    $this->owner = User::factory()->facility()->create();
    $this->visit = Visit::factory()->for($this->owner, 'facility')->create();
});

test('the owner can add a pass type', function () {
    $component = Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit]);

    fillVisitMenuForm($component, validVisitMenuInput());

    $component->call('saveVisitMenu')
        ->assertHasNoErrors()
        ->assertSet('visitMenuForm.name', '')
        ->assertSet('visitMenuForm.price', '')
        ->assertSet('visitMenuForm.capacity', '')
        ->assertSet('visitMenuForm.sales_starts_at', '')
        ->assertSet('visitMenuForm.sales_ends_at', '')
        ->assertSet('editingVisitMenuId', null);

    $visitMenu = VisitMenu::sole();
    expect($visitMenu->visit_id)->toBe($this->visit->id)
        ->and($visitMenu->name)->toBe('一般')
        ->and($visitMenu->price)->toBe(3000)
        ->and($visitMenu->capacity)->toBe(50)
        ->and($visitMenu->sales_starts_at->format('Y-m-d\TH:i'))->toBe('2030-01-01T10:00')
        ->and($visitMenu->sales_ends_at->format('Y-m-d\TH:i'))->toBe('2030-01-31T18:00');
});

test('price and capacity given as strings are saved as integers', function () {
    $component = Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit]);

    fillVisitMenuForm($component, validVisitMenuInput(['price' => '3000', 'capacity' => '50']));

    $component->call('saveVisitMenu')->assertHasNoErrors();

    $visitMenu = VisitMenu::sole();
    expect($visitMenu->price)->toBe(3000)
        ->and($visitMenu->capacity)->toBe(50)
        ->and($visitMenu->getRawOriginal('price'))->toBeInt()
        ->and($visitMenu->getRawOriginal('capacity'))->toBeInt();
});

test('the owner can edit a pass type without creating a new one', function () {
    $visitMenu = VisitMenu::factory()->for($this->visit)->create([
        'name' => '一般',
        'price' => 3000,
        'capacity' => 50,
        'sales_starts_at' => '2030-01-01 10:00:00',
        'sales_ends_at' => '2030-01-31 18:00:00',
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->call('editVisitMenu', $visitMenu->id)
        ->assertSet('editingVisitMenuId', $visitMenu->id)
        ->assertSet('visitMenuForm.name', '一般')
        ->assertSet('visitMenuForm.price', 3000)
        ->assertSet('visitMenuForm.capacity', 50)
        ->assertSet('visitMenuForm.sales_starts_at', '2030-01-01T10:00')
        ->assertSet('visitMenuForm.sales_ends_at', '2030-01-31T18:00');

    $component->set('visitMenuForm.name', '学生')
        ->set('visitMenuForm.price', '1500')
        ->call('saveVisitMenu')
        ->assertHasNoErrors()
        ->assertSet('editingVisitMenuId', null)
        ->assertSet('visitMenuForm.name', '');

    expect(VisitMenu::count())->toBe(1);

    $fresh = $visitMenu->fresh();
    expect($fresh->name)->toBe('学生')
        ->and($fresh->price)->toBe(1500)
        ->and($fresh->capacity)->toBe(50)
        ->and($fresh->visit_id)->toBe($this->visit->id);
});

test('cancelling an edit clears the form and keeps the data', function () {
    $visitMenu = VisitMenu::factory()->for($this->visit)->create(['name' => '一般']);

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->call('editVisitMenu', $visitMenu->id)
        ->set('visitMenuForm.name', '書き換え途中')
        ->call('cancelVisitMenuEdit')
        ->assertSet('editingVisitMenuId', null)
        ->assertSet('visitMenuForm.name', '')
        ->assertSet('visitMenuForm.price', '')
        ->assertSet('visitMenuForm.capacity', '');

    expect($visitMenu->fresh()->name)->toBe('一般')
        ->and(VisitMenu::count())->toBe(1);
});

test('the owner can delete the last pass type of a draft visit', function () {
    $visitMenu = VisitMenu::factory()->for($this->visit)->create();

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->call('deleteVisitMenu', $visitMenu->id)
        ->assertHasNoErrors();

    expect(VisitMenu::count())->toBe(0);
});

test('deleting the pass type being edited resets the form', function () {
    $visitMenu = VisitMenu::factory()->for($this->visit)->create();

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->call('editVisitMenu', $visitMenu->id)
        ->assertSet('editingVisitMenuId', $visitMenu->id)
        ->call('deleteVisitMenu', $visitMenu->id)
        ->assertSet('editingVisitMenuId', null)
        ->assertSet('visitMenuForm.name', '')
        ->assertSet('visitMenuForm.price', '');

    expect(VisitMenu::count())->toBe(0);
});

test('invalid pass type input is rejected on create', function (array $overrides, array $errors) {
    $component = Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit]);

    fillVisitMenuForm($component, validVisitMenuInput($overrides));

    $component->call('saveVisitMenu')->assertHasErrors($errors);

    expect(VisitMenu::count())->toBe(0);
})->with([
    'name too long' => [['name' => str_repeat('あ', 51)], ['visitMenuForm.name' => 'max']],
    'name empty' => [['name' => ''], ['visitMenuForm.name' => 'required']],
    'negative price' => [['price' => -1], ['visitMenuForm.price' => 'min']],
    'price too large' => [['price' => 10000000], ['visitMenuForm.price' => 'max']],
    'decimal price' => [['price' => '1.5'], ['visitMenuForm.price' => 'integer']],
    'text price' => [['price' => 'abc'], ['visitMenuForm.price' => 'integer']],
    'empty price' => [['price' => ''], ['visitMenuForm.price' => 'required']],
    'zero capacity' => [['capacity' => 0], ['visitMenuForm.capacity' => 'min']],
    'capacity too large' => [['capacity' => 100001], ['visitMenuForm.capacity' => 'max']],
    'decimal capacity' => [['capacity' => '1.5'], ['visitMenuForm.capacity' => 'integer']],
    'text capacity' => [['capacity' => 'abc'], ['visitMenuForm.capacity' => 'integer']],
    'empty capacity' => [['capacity' => ''], ['visitMenuForm.capacity' => 'required']],
    'empty start' => [['sales_starts_at' => ''], ['visitMenuForm.sales_starts_at' => 'required']],
    'bad date format' => [['sales_starts_at' => '2030/01/01'], ['visitMenuForm.sales_starts_at' => 'date_format']],
    'end before start' => [['sales_ends_at' => '2029-12-31T10:00'], ['visitMenuForm.sales_ends_at' => 'after']],
    'end equals start' => [['sales_ends_at' => '2030-01-01T10:00'], ['visitMenuForm.sales_ends_at' => 'after']],
]);

test('boundary values are accepted', function (array $overrides) {
    $component = Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit]);

    fillVisitMenuForm($component, validVisitMenuInput($overrides));

    $component->call('saveVisitMenu')->assertHasNoErrors();

    expect(VisitMenu::count())->toBe(1);
})->with([
    'name of 50 characters' => [['name' => str_repeat('あ', 50)]],
    'free price' => [['price' => 0]],
    'free price as string' => [['price' => '0']],
    'capacity of 1' => [['capacity' => 1]],
    'max price and capacity' => [['price' => 9999999, 'capacity' => 100000]],
]);

test('invalid input is rejected on update and the data is unchanged', function () {
    $visitMenu = VisitMenu::factory()->for($this->visit)->create(['name' => '一般', 'price' => 3000]);

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->call('editVisitMenu', $visitMenu->id)
        ->set('visitMenuForm.name', '')
        ->set('visitMenuForm.price', '')
        ->call('saveVisitMenu')
        ->assertHasErrors(['visitMenuForm.name' => 'required', 'visitMenuForm.price' => 'required'])
        ->assertSet('editingVisitMenuId', $visitMenu->id);

    $fresh = $visitMenu->fresh();
    expect($fresh->name)->toBe('一般')
        ->and($fresh->price)->toBe(3000);
});

test('other facilities cannot open the edit page to manage pass types', function () {
    $other = User::factory()->facility()->create();

    Livewire::actingAs($other)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->assertForbidden();
});

test('pass type methods re-check authorization after ownership changes', function (string $method) {
    $visitMenu = VisitMenu::factory()->for($this->visit)->create(['name' => '一般', 'price' => 3000]);
    $other = User::factory()->facility()->create();

    $component = Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit]);

    fillVisitMenuForm($component, validVisitMenuInput(['name' => '乗っ取り券']));

    $this->visit->forceFill(['facility_id' => $other->id])->save();

    $takesId = in_array($method, ['editVisitMenu', 'deleteVisitMenu'], true);

    $component->call($method, ...($takesId ? [$visitMenu->id] : []))
        ->assertForbidden();

    $fresh = $visitMenu->fresh();
    expect($fresh)->not->toBeNull()
        ->and($fresh->name)->toBe('一般')
        ->and($fresh->price)->toBe(3000)
        ->and(VisitMenu::count())->toBe(1);
})->with(['saveVisitMenu', 'editVisitMenu', 'deleteVisitMenu', 'cancelVisitMenuEdit']);

test('saving an edit is forbidden after ownership changes', function () {
    $visitMenu = VisitMenu::factory()->for($this->visit)->create(['name' => '一般']);
    $other = User::factory()->facility()->create();

    $component = Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->call('editVisitMenu', $visitMenu->id)
        ->set('visitMenuForm.name', '乗っ取り券');

    $this->visit->forceFill(['facility_id' => $other->id])->save();

    $component->call('saveVisitMenu')->assertForbidden();

    expect($visitMenu->fresh()->name)->toBe('一般');
});

test('pass types of other visits cannot be edited or deleted', function (string $method) {
    $otherVisit = Visit::factory()->create();
    $foreign = VisitMenu::factory()->for($otherVisit)->create(['name' => '他人のメニュー']);

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->call($method, $foreign->id)
        ->assertNotFound();

    $fresh = $foreign->fresh();
    expect($fresh)->not->toBeNull()
        ->and($fresh->name)->toBe('他人のメニュー')
        ->and($fresh->visit_id)->toBe($otherVisit->id);
})->with(['editVisitMenu', 'deleteVisitMenu']);

test('a draft visit with no pass types cannot be published', function () {
    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->assertSee('公開するにはメニューが1つ以上必要です。')
        ->call('togglePublish')
        ->assertHasErrors('publish');

    expect($this->visit->fresh()->status)->toBe(VisitStatus::Draft);
});

test('a draft visit with a pass type can be published', function () {
    VisitMenu::factory()->for($this->visit)->create();

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->assertDontSee('公開するにはメニューが1つ以上必要です。')
        ->call('togglePublish')
        ->assertHasNoErrors();

    expect($this->visit->fresh()->status)->toBe(VisitStatus::Published);
});

test('the last pass type cannot be deleted unless the visit is a draft', function (string $state, string $message) {
    $visit = Visit::factory()->for($this->owner, 'facility')->{$state}()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create();

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $visit])
        ->call('deleteVisitMenu', $visitMenu->id)
        ->assertHasErrors('visitMenus')
        ->assertSee($message);

    expect($visitMenu->fresh())->not->toBeNull()
        ->and($visit->fresh()->status)->toBe(VisitStatus::from($state));
})->with([
    'published' => ['published', '公開中の慰問の最後のメニューは削除できません。'],
    'unpublished' => ['unpublished', '非公開の慰問の最後のメニューは削除できません。'],
]);

test('the pass type being edited cannot be switched from the browser', function () {
    $editing = VisitMenu::factory()->for($this->visit)->create();
    $another = VisitMenu::factory()->for($this->visit)->create();

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->call('editVisitMenu', $editing->id)
        ->set('editingVisitMenuId', $another->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('a pass type of a published visit can be deleted when another remains', function () {
    $visit = Visit::factory()->for($this->owner, 'facility')->published()->create();
    $first = VisitMenu::factory()->for($visit)->create();
    $second = VisitMenu::factory()->for($visit)->create();

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $visit])
        ->call('deleteVisitMenu', $first->id)
        ->assertHasNoErrors();

    expect($first->fresh())->toBeNull()
        ->and($second->fresh())->not->toBeNull();
});

test('pass types are listed with formatted price and capacity', function () {
    VisitMenu::factory()->for($this->visit)->create([
        'name' => '特別観覧席',
        'price' => 3000,
        'capacity' => 50,
    ]);

    Livewire::actingAs($this->owner)
        ->test('pages::facility.visits.edit', ['visit' => $this->visit])
        ->assertSee('特別観覧席')
        ->assertSee('3,000円')
        ->assertSee('50名');
});
