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
    {{-- 慰問日記のページができたら、div を route() への a に差し替える --}}
    <div
        class="flex items-center gap-4 rounded-3xl border-2 border-accent bg-zinc-50 p-4 sm:p-6 dark:bg-white/10"
        data-test="visit-diary-banner"
    >
        <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-accent text-accent-foreground">
            <flux:icon.book-open />
        </div>

        <div class="min-w-0 flex-1 space-y-1">
            <flux:heading size="lg" level="2">慰問日記</flux:heading>
            <flux:text>慰問の様子を、写真とことばでお届けします。</flux:text>
        </div>

        <flux:badge color="zinc" class="shrink-0">準備中</flux:badge>
    </div>

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
