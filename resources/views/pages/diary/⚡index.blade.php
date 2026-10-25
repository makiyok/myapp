<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('慰問日記')] class extends Component {
    /**
     * 慰問日記の記事。いまは画面に直接書いている。
     *
     * @return list<array{illustration: string, title: string, body: list<string>, voices: list<array{icon: string, label: string, quote: string, speaker: string}>}>
     */
    #[Computed]
    public function entries(): array
    {
        return [
            [
                'illustration' => 'manzai-illustration',
                'title' => '小学生の漫才コンビがやってきました',
                'body' => [
                    '小学生の漫才コンビが、施設のみなさんの前で漫才を披露してくれました。',
                    '「どうも〜！」の元気なあいさつから始まり、息の合ったボケとツッコミに、観覧した10人のみなさんは大きな笑顔に。',
                    '最後は会場いっぱいの拍手で幕を閉じました。',
                ],
                'voices' => [
                    ['icon' => 'heart', 'label' => '高齢者の感想', 'quote' => '孫と同じくらいの子が一生懸命で、久しぶりにお腹の底から笑いました。また来てね。', 'speaker' => '施設の利用者（80代）'],
                    ['icon' => 'star', 'label' => '小学生の感想', 'quote' => '緊張したけど、みんなが笑ってくれてうれしかった！次はもっとおもしろいネタを考えてきます。', 'speaker' => '漫才コンビ（小学5年生）'],
                ],
            ],
            [
                'illustration' => 'dance-illustration',
                'title' => '幼稚園のみんながダンスを届けてくれました',
                'body' => [
                    '黄色い帽子をかぶった幼稚園のみんなが、元気いっぱいのダンスを見せてくれました。',
                    '音楽に合わせて手を広げたり、足を上げたりするたびに、施設のみなさんからは手拍子と「じょうず〜！」の声。',
                    '車いすの方も手を振って応えて、会場がひとつになりました。',
                ],
                'voices' => [
                    ['icon' => 'heart', 'label' => '高齢者の感想', 'quote' => '小さな手足でいっしょうけんめい踊る姿に、元気をもらいました。思わずいっしょに手拍子しました。', 'speaker' => '施設の利用者（70代）'],
                    ['icon' => 'star', 'label' => '幼稚園児の感想', 'quote' => 'おじいちゃんとおばあちゃんが手をふってくれて、うれしかった！またおどりにきたい！', 'speaker' => '幼稚園の年長さん'],
                ],
            ],
        ];
    }
}; ?>

<section class="w-full max-w-5xl space-y-6">
    <div class="space-y-2">
        <flux:heading size="xl" level="1">慰問日記</flux:heading>
        <flux:text size="lg">慰問の様子を、写真とことばでお届けします。</flux:text>
    </div>

    @foreach ($this->entries as $entry)
        <article
            class="space-y-4 rounded-3xl border-2 border-zinc-200 bg-white p-4 sm:p-6 dark:bg-white/5"
            wire:key="diary-entry-{{ $loop->index }}"
            data-test="diary-entry"
        >
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="overflow-hidden rounded-2xl border-2 border-zinc-200 lg:col-span-2">
                    <x-dynamic-component :component="$entry['illustration']" class="block h-auto w-full" />
                </div>

                <div class="flex flex-col gap-4" data-test="diary-voices">
                    @foreach ($entry['voices'] as $voice)
                        <figure class="flex-1 space-y-2 rounded-2xl bg-zinc-50 p-4 dark:bg-white/10">
                            <figcaption class="flex items-center gap-2">
                                <flux:icon :name="$voice['icon']" variant="solid" class="size-5 text-accent-content" />
                                <flux:heading size="lg" level="3">{{ $voice['label'] }}</flux:heading>
                            </figcaption>
                            <blockquote>
                                <flux:text size="lg" class="leading-relaxed">「{{ $voice['quote'] }}」</flux:text>
                            </blockquote>
                            <flux:text>{{ $voice['speaker'] }}</flux:text>
                        </figure>
                    @endforeach
                </div>
            </div>

            <flux:heading size="lg" level="2" class="text-xl!">{{ $entry['title'] }}</flux:heading>

            <flux:text size="xl" class="leading-relaxed">
                @foreach ($entry['body'] as $sentence)
                    {{ $sentence }}
                @endforeach
            </flux:text>
        </article>
    @endforeach

    <flux:button variant="ghost" icon="arrow-left" :href="route('home')" wire:navigate>慰問一覧へ戻る</flux:button>
</section>
