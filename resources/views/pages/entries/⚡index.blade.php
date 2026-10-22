<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('マイ参加証')] class extends Component {
    use WithPagination;

    /**
     * ログイン中のユーザー自身の申し込み（新しい順）
     */
    #[Computed]
    public function entries(): LengthAwarePaginator
    {
        return Auth::user()->entries()->with('visit')->latest()->latest('id')->paginate(10);
    }
}; ?>

<section class="w-full space-y-6">
    <flux:heading size="xl" level="1">マイ参加証</flux:heading>

    @if ($this->entries->isEmpty())
        <flux:text>まだ申し込みがありません。</flux:text>

        <flux:button :href="route('home')" wire:navigate>慰問を探す</flux:button>
    @else
        <flux:table :paginate="$this->entries">
            <flux:table.columns>
                <flux:table.column>慰問名</flux:table.column>
                <flux:table.column>開催日</flux:table.column>
                <flux:table.column>申し込み日</flux:table.column>
                <flux:table.column align="end">合計金額</flux:table.column>
                <flux:table.column>状態</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->entries as $entry)
                    <flux:table.row :key="$entry->id">
                        <flux:table.cell>{{ $entry->visit->title }}</flux:table.cell>
                        <flux:table.cell>{{ $entry->visit->starts_at->isoFormat('M月D日(ddd) HH:mm') }}</flux:table.cell>
                        <flux:table.cell>{{ $entry->created_at->isoFormat('M月D日(ddd) HH:mm') }}</flux:table.cell>
                        <flux:table.cell align="end">{{ number_format($entry->total_amount) }}円</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$entry->status->color()">{{ $entry->status->label() }}</flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
