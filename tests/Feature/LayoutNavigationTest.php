<?php

use App\Models\User;
use Illuminate\Testing\TestView;

function renderAppLayout(): TestView
{
    return test()->blade('<x-layouts::app>本文</x-layouts::app>');
}

test('guests can see the layout with login and register links', function () {
    renderAppLayout()
        ->assertSee('本文')
        ->assertSee('慰問一覧')
        ->assertSee('ログイン')
        ->assertSee('会員登録')
        ->assertSee(route('login'))
        ->assertSee(route('register'))
        ->assertDontSee('マイ参加証')
        ->assertDontSee('設定')
        ->assertDontSee('福祉施設メニュー')
        ->assertDontSee('慰問管理')
        ->assertDontSee('ユーザー管理');
});

test('visitors see my passes and settings but no facility or admin links', function () {
    $this->actingAs(User::factory()->create());

    renderAppLayout()
        ->assertSee('慰問一覧')
        ->assertSee('マイ参加証')
        ->assertSee(url('/entries'))
        ->assertSee('設定')
        ->assertDontSee('会員登録')
        ->assertDontSee('福祉施設メニュー')
        ->assertDontSee('慰問管理')
        ->assertDontSee('ユーザー管理');
});

test('facilities see the facility menu instead of my passes', function () {
    $this->actingAs(User::factory()->facility()->create());

    renderAppLayout()
        ->assertDontSee('マイ参加証')
        ->assertSee('福祉施設メニュー')
        ->assertSee(route('facility.visits.index'))
        ->assertDontSee('慰問管理')
        ->assertDontSee('ユーザー管理');
});

test('admins also see visit and user management', function () {
    $this->actingAs(User::factory()->admin()->create());

    renderAppLayout()
        ->assertSee('マイ参加証')
        ->assertSee('慰問管理')
        ->assertSee(url('/admin/visits'))
        ->assertSee('ユーザー管理')
        ->assertSee(url('/admin/users'))
        ->assertDontSee('福祉施設メニュー');
});

test('the logo at the top left reads 慰問ステージ', function () {
    $this->get(route('diary.index'))
        ->assertOk()
        ->assertSee('data-flux-brand', escape: false)
        ->assertSeeInOrder(['data-flux-brand', '慰問ステージ'], escape: false);
});

test('a logged-in page still renders inside the header layout', function () {
    $this->actingAs(User::factory()->facility()->create())
        ->get(route('facility.visits.index'))
        ->assertOk()
        ->assertSee('福祉施設メニュー')
        ->assertSee('ログアウト');
});
