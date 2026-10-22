<?php

use App\Enums\UserRole;
use App\Models\User;

function registrationInput(array $overrides = []): array
{
    return [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'visitor',
        ...$overrides,
    ];
}

test('users can register as an facility', function () {
    $this->post(route('register.store'), registrationInput(['role' => 'facility']))
        ->assertSessionHasNoErrors();

    expect(User::firstWhere('email', 'test@example.com')->role)->toBe(UserRole::Facility);
});

test('users can register as a visitor', function () {
    $this->post(route('register.store'), registrationInput())
        ->assertSessionHasNoErrors();

    expect(User::firstWhere('email', 'test@example.com')->role)->toBe(UserRole::Visitor);
});

test('admin cannot be chosen at registration', function (string $role) {
    $this->post(route('register.store'), registrationInput(['role' => $role]))
        ->assertSessionHasErrors('role');

    expect(User::count())->toBe(0);
    $this->assertGuest();
})->with(['admin', 'unknown']);

test('role is required at registration', function () {
    $this->post(route('register.store'), registrationInput(['role' => '']))
        ->assertSessionHasErrors('role');
});

test('role labels are in Japanese', function () {
    expect(UserRole::Visitor->label())->toBe('慰問者')
        ->and(UserRole::Facility->label())->toBe('福祉施設')
        ->and(UserRole::Admin->label())->toBe('管理者');
});

test('role helper methods follow the role', function () {
    expect(User::factory()->admin()->make()->isAdmin())->toBeTrue()
        ->and(User::factory()->facility()->make()->isFacility())->toBeTrue()
        ->and(User::factory()->make()->isVisitor())->toBeTrue()
        ->and(User::factory()->facility()->make()->isAdmin())->toBeFalse();
});

test('an invalid role shows an error on the registration form', function () {
    $this->from(route('register'))
        ->followingRedirects()
        ->post(route('register.store'), registrationInput(['role' => 'admin']))
        ->assertSee(__('validation.in', ['attribute' => __('validation.attributes.role')]));
});
