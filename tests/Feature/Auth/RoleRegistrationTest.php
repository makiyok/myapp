<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Http;

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

/**
 * 福祉施設として登録するときの入力。法人番号は国税庁の公表番号（チェック用の数字が合っている）。
 */
function facilityRegistrationInput(array $overrides = []): array
{
    return registrationInput([
        'role' => 'facility',
        'facility_number' => '1234567890',
        'corporate_number' => '7000012050002',
        ...$overrides,
    ]);
}

/**
 * 本番と同じ入力チェックで登録させる。本番では CSRF の確認も有効になるので、それだけ外す。
 */
function useProductionRules(): void
{
    test()->withoutMiddleware(PreventRequestForgery::class);
    app()['env'] = 'production';
}

test('users can register as an facility with a facility number and a corporate number', function () {
    $this->post(route('register.store'), facilityRegistrationInput())
        ->assertSessionHasNoErrors();

    $user = User::firstWhere('email', 'test@example.com');

    expect($user->role)->toBe(UserRole::Facility)
        ->and($user->facility_number)->toBe('1234567890')
        ->and($user->corporate_number)->toBe('7000012050002');
});

test('facilities must enter a facility number and a corporate number', function (string $field) {
    $this->post(route('register.store'), facilityRegistrationInput([$field => '']))
        ->assertSessionHasErrors($field);

    expect(User::count())->toBe(0);
})->with(['facility_number', 'corporate_number']);

test('in production the facility number must be 10 digits', function (string $facilityNumber) {
    useProductionRules();

    $this->post(route('register.store'), facilityRegistrationInput(['facility_number' => $facilityNumber]))
        ->assertSessionHasErrors('facility_number');

    expect(User::count())->toBe(0);
})->with(['123456789', '12345678901', '12345abcde', '１２３４５６７８９０']);

test('in production the corporate number must be 13 digits', function (string $corporateNumber) {
    useProductionRules();

    $this->post(route('register.store'), facilityRegistrationInput(['corporate_number' => $corporateNumber]))
        ->assertSessionHasErrors('corporate_number');

    expect(User::count())->toBe(0);
})->with(['700001205000', '70000120500021', '700001205000a', '７０００１２０５０００２']);

test('in production the check digit of the corporate number must match', function () {
    useProductionRules();

    $this->post(route('register.store'), facilityRegistrationInput(['corporate_number' => '1000012050002']))
        ->assertSessionHasErrors(['corporate_number' => '法人番号が正しくありません。番号をもう一度ご確認ください。']);

    expect(User::count())->toBe(0);
});

test('outside production facility and corporate numbers in any format are accepted', function () {
    $this->post(route('register.store'), facilityRegistrationInput(['facility_number' => 'abc', 'corporate_number' => '１２３']))
        ->assertSessionHasNoErrors();

    $user = User::firstWhere('email', 'test@example.com');

    expect($user->facility_number)->toBe('abc')
        ->and($user->corporate_number)->toBe('１２３');
});

test('outside production numbers longer than their columns are rejected', function (string $field, int $length) {
    $this->post(route('register.store'), facilityRegistrationInput([$field => str_repeat('1', $length + 1)]))
        ->assertSessionHasErrors($field);

    expect(User::count())->toBe(0);
})->with([
    'facility number' => ['facility_number', 10],
    'corporate number' => ['corporate_number', 13],
]);

test('outside production any password is accepted', function () {
    $this->post(route('register.store'), registrationInput(['password' => '1', 'password_confirmation' => '1']))
        ->assertSessionHasNoErrors();

    $this->assertAuthenticated();
});

test('in production a short password is rejected', function () {
    useProductionRules();
    Http::preventStrayRequests();

    $this->post(route('register.store'), registrationInput(['password' => '1', 'password_confirmation' => '1']))
        ->assertSessionHasErrors('password');

    expect(User::count())->toBe(0);
});

test('the password confirmation must match', function () {
    $this->post(route('register.store'), registrationInput(['password' => '1', 'password_confirmation' => '2']))
        ->assertSessionHasErrors('password');

    expect(User::count())->toBe(0);
});

test('users can register as a visitor', function () {
    $this->post(route('register.store'), registrationInput())
        ->assertSessionHasNoErrors();

    expect(User::firstWhere('email', 'test@example.com')->role)->toBe(UserRole::Visitor);
});

test('facility and corporate numbers sent by a visitor are not saved', function () {
    $this->post(route('register.store'), registrationInput(['facility_number' => 'not-a-number', 'corporate_number' => 'not-a-number']))
        ->assertSessionHasNoErrors();

    $user = User::firstWhere('email', 'test@example.com');

    expect($user->facility_number)->toBeNull()
        ->and($user->corporate_number)->toBeNull();
});

test('the facility number field is shown only after choosing a facility', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('事業所番号')
        ->assertSee('法人番号')
        ->assertSeeInOrder(['x-show="role === \'facility\'"', 'style="display: none;"', 'data-test="facility-number-field"'], escape: false);
});

test('the facility number field stays open when a facility comes back with errors', function () {
    $this->from(route('register'))
        ->followingRedirects()
        ->post(route('register.store'), facilityRegistrationInput(['facility_number' => '']))
        ->assertSee('事業所番号')
        ->assertDontSee('style="display: none;"', escape: false);
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
