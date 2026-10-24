<?php

use App\Models\User;

test('guests can read the visit diary with the manzai illustration', function () {
    $this->get(route('diary.index'))
        ->assertOk()
        ->assertSee('慰問日記')
        ->assertSee('小学生の漫才コンビがやってきました')
        ->assertSee('小学生の漫才コンビと、笑顔で観覧する10人の高齢者のイラスト');
});

test('logged in users can read the visit diary', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('diary.index'))
        ->assertOk()
        ->assertSee('小学生の漫才コンビがやってきました');
});
