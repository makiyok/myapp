<?php

use App\Models\User;

test('guests can read the visit diary with the manzai illustration', function () {
    $this->get(route('diary.index'))
        ->assertOk()
        ->assertSee('慰問日記')
        ->assertSee('小学生の漫才コンビがやってきました')
        ->assertSee('小学生の漫才コンビと、笑顔で観覧する10人の高齢者のイラスト');
});

test('the impressions of the elderly and the children are shown next to the illustration', function () {
    $this->get(route('diary.index'))
        ->assertOk()
        ->assertSeeInOrder(['小学生の漫才コンビと、笑顔で観覧する10人の高齢者のイラスト', '高齢者の感想', '小学生の感想']);
});

test('the dance diary is shown with its illustration and impressions', function () {
    $this->get(route('diary.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'ダンスする幼稚園児と、それを楽しむ高齢者のイラスト',
            '高齢者の感想',
            '幼稚園児の感想',
            '幼稚園のみんながダンスを届けてくれました',
        ]);
});

test('logged in users can read the visit diary', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('diary.index'))
        ->assertOk()
        ->assertSee('小学生の漫才コンビがやってきました');
});
