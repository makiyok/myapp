<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('慰問管理')] class extends Component {
    use WithPagination;

    /**
     * ログイン中の福祉施設自身の慰問（開催日の新しい順）
     */
    #[Computed]
    public function visits(): LengthAwarePaginator
    {
        return Auth::user()->visits()->latest('starts_at')->paginate(10);
    }
}; ?>

<section class="w-full space-y-6 [&_td]:text-lg [&_th]:text-lg [&_[data-flux-pagination]_*]:text-base">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">慰問管理</flux:heading>

        <flux:button variant="primary" icon="plus" :href="route('facility.visits.create')" wire:navigate>
            慰問を登録
        </flux:button>
    </div>

    @if ($this->visits->isEmpty())
        <flux:text size="lg">まだ慰問がありません。「慰問を登録」から作成できます。</flux:text>
    @else
        <flux:table :paginate="$this->visits">
            <flux:table.columns>
                <flux:table.column>慰問名</flux:table.column>
                <flux:table.column>開催日</flux:table.column>
                <flux:table.column>状態</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->visits as $visit)
                    <flux:table.row :key="$visit->id">
                        <flux:table.cell>{{ $visit->title }}</flux:table.cell>
                        <flux:table.cell>{{ $visit->starts_at->isoFormat('M月D日(ddd) HH:mm') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$visit->status->color()" class="text-base!">{{ $visit->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button :href="route('facility.visits.edit', $visit)" wire:navigate class="text-base!">
                                編集
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
