<?php

use App\Enums\VisitUnit;
use App\Livewire\Forms\VisitForm;
use App\Models\Visit;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('慰問登録')] class extends Component {
    public VisitForm $form;

    public function mount(): void
    {
        $this->authorize('create', Visit::class);
    }

    /**
     * 下書きとして慰問を保存し、編集画面へ進む。
     */
    public function save(): void
    {
        $this->authorize('create', Visit::class);

        $visit = Auth::user()->visits()->create($this->form->validate());

        Flux::toast(variant: 'success', text: '慰問を下書きで保存しました。');

        $this->redirectRoute('facility.visits.edit', $visit, navigate: true);
    }
}; ?>

<section class="w-full max-w-2xl space-y-6 [&_[data-flux-label]]:text-base [&_input]:text-base [&_textarea]:text-base">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">慰問登録</flux:heading>

        <flux:button variant="ghost" :href="route('facility.visits.index')" wire:navigate>一覧へ戻る</flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="form.title" label="慰問名" type="text" required autofocus />

        <flux:textarea wire:model="form.description" label="説明" rows="6" required />

        <flux:input wire:model="form.venue" label="会場" type="text" required />

        <flux:radio.group wire:model="form.unit" label="申し込みの単位" description="漫才のコンビなど、1組で申し込んでもらう慰問は「組」を選びます。">
            @foreach (VisitUnit::cases() as $unit)
                <flux:radio :value="$unit->value" :label="$unit->label().'（'.$unit->countLabel().'で数える）'" />
            @endforeach
        </flux:radio.group>

        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input wire:model="form.starts_at" label="開始日時" type="datetime-local" required />
            <flux:input wire:model="form.ends_at" label="終了日時" type="datetime-local" required />
        </div>

        <div class="flex justify-end">
            <flux:button variant="primary" type="submit" data-test="save-visit-button">下書きで保存</flux:button>
        </div>
    </form>
</section>
