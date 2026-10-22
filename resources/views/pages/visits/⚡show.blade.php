<?php

use App\Models\Visit;
use App\Services\EntryService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('慰問詳細')] class extends Component {
    public Visit $visit;

    /**
     * メニューの ID => 人数。画面から書き換えられる値なので、申し込みを作るときに EntryService がもう一度確認する。
     *
     * @var array<array-key, mixed>
     */
    public array $quantities = [];

    public function mount(Visit $visit): void
    {
        $this->authorize('view', $visit);

        $this->visit = $visit;
        $this->quantities = $this->visitMenus->mapWithKeys(fn ($visitMenu) => [$visitMenu->id => 0])->all();
    }

    /**
     * 選んだ人数をセッションに保存して、確認画面へ進む。未ログインならログインをはさむ。
     */
    public function proceed(): void
    {
        $this->authorize('view', $this->visit);

        $this->resetErrorBag('quantities');

        $selected = [];

        foreach ($this->visitMenus as $visitMenu) {
            $quantity = $this->quantities[$visitMenu->id] ?? 0;

            if ((is_int($quantity) || (is_string($quantity) && ctype_digit($quantity))) && (int) $quantity > 0) {
                $selected[$visitMenu->id] = (int) $quantity;
            }
        }

        if ($selected === []) {
            $this->addError('quantities', '人数を選んでください。');

            return;
        }

        session()->put("purchase.{$this->visit->id}", $selected);

        $this->redirectRoute('visits.purchase', $this->visit);
    }

    /**
     * セレクトで選べる人数の上限
     */
    public function maxQuantity(int $remaining): int
    {
        return min(EntryService::MAX_QUANTITY, $remaining);
    }

    /**
     * この慰問のメニュー（販売開始の早い順）
     *
     * @return Collection<int, \App\Models\VisitMenu>
     */
    #[Computed]
    public function visitMenus(): Collection
    {
        return $this->visit->visitMenus()->orderBy('sales_starts_at')->orderBy('id')->get();
    }
}; ?>

<section class="w-full max-w-3xl space-y-6">
    <div class="flex items-center gap-3">
        <flux:heading size="xl" level="1">{{ $visit->title }}</flux:heading>

        @unless ($visit->isPublished())
            <flux:badge :color="$visit->status->color()">{{ $visit->status->label() }}</flux:badge>
        @endunless
    </div>

    <dl class="grid gap-4 sm:grid-cols-2">
        <div>
            <dt><flux:text>日時</flux:text></dt>
            <dd>
                {{ $visit->starts_at->isoFormat('M月D日(ddd) HH:mm') }} 〜
                {{ $visit->ends_at->isoFormat('M月D日(ddd) HH:mm') }}
            </dd>
        </div>

        <div>
            <dt><flux:text>会場</flux:text></dt>
            <dd>{{ $visit->venue }}</dd>
        </div>
    </dl>

    <p class="whitespace-pre-line">{{ $visit->description }}</p>

    <flux:separator />

    <div class="space-y-4">
        <flux:heading size="lg" level="2">参加証</flux:heading>

        @if ($this->visitMenus->isEmpty())
            <flux:text>この慰問の参加証はまだ登録されていません。</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>メニュー名</flux:table.column>
                    <flux:table.column align="end">価格</flux:table.column>
                    <flux:table.column align="end">残り人数</flux:table.column>
                    <flux:table.column>販売期間</flux:table.column>
                    <flux:table.column></flux:table.column>
                    <flux:table.column align="end">人数</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->visitMenus as $visitMenu)
                        @php($remaining = $visitMenu->remaining())

                        <flux:table.row :key="$visitMenu->id">
                            <flux:table.cell>{{ $visitMenu->name }}</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($visitMenu->price) }}円</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($remaining) }}名</flux:table.cell>
                            <flux:table.cell>
                                {{ $visitMenu->sales_starts_at->isoFormat('M月D日(ddd) HH:mm') }} 〜<br>
                                {{ $visitMenu->sales_ends_at->isoFormat('M月D日(ddd) HH:mm') }}
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if (now()->lt($visitMenu->sales_starts_at))
                                    <flux:badge size="sm" color="zinc">販売前</flux:badge>
                                @elseif (! $visitMenu->isOnSale())
                                    <flux:badge size="sm" color="zinc">販売終了</flux:badge>
                                @elseif ($remaining === 0)
                                    <flux:badge size="sm" color="red">売り切れ</flux:badge>
                                @else
                                    <flux:badge size="sm" color="green">販売中</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($visitMenu->isOnSale() && $remaining > 0)
                                    <flux:select
                                        wire:model="quantities.{{ $visitMenu->id }}"
                                        class="w-24"
                                        aria-label="{{ $visitMenu->name }}の人数"
                                        data-test="quantity-{{ $visitMenu->id }}"
                                    >
                                        @foreach (range(0, $this->maxQuantity($remaining)) as $quantity)
                                            <flux:select.option value="{{ $quantity }}">{{ $quantity }}名</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            @if ($visit->isPublished())
            <div class="flex flex-col items-end gap-2">
                <flux:error name="quantities" />

                <flux:button variant="primary" wire:click="proceed" data-test="proceed-button">申し込み手続きへ進む</flux:button>

                @guest
                    <flux:text>申し込みにはログインが必要です。</flux:text>
                @endguest
            </div>
            @endif
        @endif
    </div>
</section>
