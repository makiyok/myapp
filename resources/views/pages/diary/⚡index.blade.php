<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('慰問日記')] class extends Component {
    //
}; ?>

<section class="w-full max-w-3xl space-y-6">
    <div class="space-y-2">
        <flux:heading size="xl" level="1">慰問日記</flux:heading>
        <flux:text>慰問の様子を、写真とことばでお届けします。</flux:text>
    </div>

    <article class="space-y-4 rounded-3xl border-2 border-zinc-200 bg-white p-4 sm:p-6 dark:bg-white/5" data-test="diary-entry">
        <div class="overflow-hidden rounded-2xl border-2 border-zinc-200">
            <x-manzai-illustration class="block h-auto w-full" />
        </div>

        <flux:heading size="lg" level="2">小学生の漫才コンビがやってきました</flux:heading>

        <flux:text>
            小学生の漫才コンビが、施設のみなさんの前で漫才を披露してくれました。
            「どうも〜！」の元気なあいさつから始まり、息の合ったボケとツッコミに、観覧した10人のみなさんは大きな笑顔に。
            最後は会場いっぱいの拍手で幕を閉じました。
        </flux:text>
    </article>

    <flux:button variant="ghost" icon="arrow-left" :href="route('home')" wire:navigate>慰問一覧へ戻る</flux:button>
</section>
