<?php

use App\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('慰問一覧')] class extends Component {
    use WithPagination;

    /**
     * 公開中かつ開催前の慰問（開催日が近い順）
     */
    #[Computed]
    public function visits(): LengthAwarePaginator
    {
        return Visit::query()->published()->upcoming()->oldest('starts_at')->oldest('id')->paginate(12);
    }
}; ?>

<section class="w-full space-y-6">
    <flux:heading size="xl" level="1">慰問一覧</flux:heading>

    @if ($this->visits->isEmpty())
        <flux:text>現在、開催予定の慰問はありません。</flux:text>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->visits as $visit)
                <a
                    href="{{ route('visits.show', $visit) }}"
                    wire:navigate
                    wire:key="visit-{{ $visit->id }}"
                    class="block space-y-2 rounded-xl border border-zinc-200 p-4 hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500"
                >
                    <flux:heading size="lg" level="2">{{ $visit->title }}</flux:heading>
                    <flux:text>{{ $visit->starts_at->isoFormat('M月D日(ddd) HH:mm') }}</flux:text>
                    <flux:text>{{ $visit->venue }}</flux:text>
                </a>
            @endforeach
        </div>

        <flux:pagination :paginator="$this->visits" />
    @endif
</section>
