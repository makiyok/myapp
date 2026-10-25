<?php

use App\Enums\VisitStatus;
use App\Livewire\Forms\VisitForm;
use App\Livewire\Forms\VisitMenuForm;
use App\Models\Visit;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('慰問編集')] class extends Component {
    public Visit $visit;

    public VisitForm $form;

    public VisitMenuForm $visitMenuForm;

    /**
     * 編集中のメニュー。null のときはフォームが「追加」になる。
     */
    #[Locked]
    public ?int $editingVisitMenuId = null;

    public function mount(Visit $visit): void
    {
        $this->authorize('update', $visit);

        $this->visit = $visit;
        $this->form->setVisit($visit);
    }

    public function save(): void
    {
        $this->authorize('update', $this->visit);

        $this->visit->update($this->form->validate());

        Flux::toast(variant: 'success', text: '慰問を保存しました。');
    }

    /**
     * 下書きと公開中を切り替える。管理者が非公開にした慰問は福祉施設には戻せない。
     */
    public function togglePublish(): void
    {
        $this->authorize('togglePublish', $this->visit);

        $this->resetErrorBag('publish');

        if (! $this->visit->isPublished() && $this->visit->visitMenus()->doesntExist()) {
            $this->addError('publish', 'メニューを1つ以上登録してから公開してください。');

            return;
        }

        $this->visit->status = $this->visit->isPublished() ? VisitStatus::Draft : VisitStatus::Published;
        $this->visit->save();

        Flux::toast(
            variant: 'success',
            text: $this->visit->isPublished() ? '慰問を公開しました。' : '慰問を下書きに戻しました。',
        );
    }

    public function deleteVisit(): void
    {
        $this->authorize('delete', $this->visit);

        $this->visit->delete();

        Flux::toast(variant: 'success', text: '慰問を削除しました。');

        $this->redirectRoute('facility.visits.index', navigate: true);
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

    /**
     * 一覧の「編集」。メニューは必ずこの慰問の中から探す（他の慰問のメニュー ID は 404）。
     */
    public function editVisitMenu(int $id): void
    {
        $this->authorize('update', $this->visit);

        $visitMenu = $this->visit->visitMenus()->findOrFail($id);

        $this->visitMenuForm->resetValidation();
        $this->visitMenuForm->setVisitMenu($visitMenu);
        $this->editingVisitMenuId = $visitMenu->id;
    }

    /**
     * 追加と編集で共用する保存。編集中のメニューがあれば更新、無ければ追加する。
     */
    public function saveVisitMenu(): void
    {
        $this->authorize('update', $this->visit);

        $payload = $this->visitMenuForm->payload();

        if ($this->editingVisitMenuId === null) {
            $this->visit->visitMenus()->create($payload);
            $message = 'メニューを追加しました。';
        } else {
            $this->visit->visitMenus()->findOrFail($this->editingVisitMenuId)->update($payload);
            $message = 'メニューを更新しました。';
        }

        $this->resetVisitMenuForm();
        $this->resetErrorBag('publish');
        unset($this->visitMenus);

        Flux::toast(variant: 'success', text: $message);
    }

    public function cancelVisitMenuEdit(): void
    {
        $this->authorize('update', $this->visit);

        $this->resetVisitMenuForm();
    }

    /**
     * メニューを0件にできるのは下書きだけ。公開中と非公開では、最後の1件の削除を断る。
     */
    public function deleteVisitMenu(int $id): void
    {
        $this->authorize('update', $this->visit);

        $visitMenu = $this->visit->visitMenus()->findOrFail($id);

        $this->resetErrorBag('visitMenus');

        if ($this->visit->status !== VisitStatus::Draft && $this->visit->visitMenus()->count() === 1) {
            $this->addError('visitMenus', $this->visit->isPublished()
                ? '公開中の慰問の最後のメニューは削除できません。先に下書きに戻してください。'
                : '非公開の慰問の最後のメニューは削除できません。');

            return;
        }

        $visitMenu->delete();

        if ($this->editingVisitMenuId === $id) {
            $this->resetVisitMenuForm();
        }

        unset($this->visitMenus);

        Flux::toast(variant: 'success', text: 'メニューを削除しました。');
    }

    private function resetVisitMenuForm(): void
    {
        $this->visitMenuForm->reset();
        $this->visitMenuForm->resetValidation();
        $this->editingVisitMenuId = null;
    }
}; ?>

<section class="w-full max-w-2xl space-y-6 [&_td]:text-base [&_th]:text-base [&_[data-flux-label]]:text-base [&_input]:text-base [&_textarea]:text-base">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <flux:heading size="xl" level="1">慰問編集</flux:heading>
            <flux:badge :color="$visit->status->color()">{{ $visit->status->label() }}</flux:badge>
        </div>

        <flux:button variant="ghost" :href="route('facility.visits.index')" wire:navigate>一覧へ戻る</flux:button>
    </div>

    @if ($visit->status === VisitStatus::Unpublished)
        <flux:callout variant="warning" icon="exclamation-triangle" heading="この慰問は管理者によって非公開にされています。" />
    @endif

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="form.title" label="慰問名" type="text" required />

        <flux:textarea wire:model="form.description" label="説明" rows="6" required />

        <flux:input wire:model="form.venue" label="会場" type="text" required />

        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input wire:model="form.starts_at" label="開始日時" type="datetime-local" required />
            <flux:input wire:model="form.ends_at" label="終了日時" type="datetime-local" required />
        </div>

        <div class="flex justify-end">
            <flux:button variant="primary" type="submit" data-test="save-visit-button">保存</flux:button>
        </div>
    </form>

    <flux:separator />

    <div class="space-y-4">
        <flux:heading size="lg" level="2" class="text-xl!">メニュー</flux:heading>

        @if ($this->visitMenus->isEmpty())
            <flux:text size="lg">まだメニューがありません。下のフォームから追加してください。</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>メニュー名</flux:table.column>
                    <flux:table.column align="end">価格</flux:table.column>
                    <flux:table.column align="end">定員</flux:table.column>
                    <flux:table.column>販売期間</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->visitMenus as $visitMenu)
                        <flux:table.row :key="$visitMenu->id">
                            <flux:table.cell>{{ $visitMenu->name }}</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($visitMenu->price) }}円</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($visitMenu->capacity) }}名</flux:table.cell>
                            <flux:table.cell>
                                {{ $visitMenu->sales_starts_at->isoFormat('M月D日(ddd) HH:mm') }} 〜<br>
                                {{ $visitMenu->sales_ends_at->isoFormat('M月D日(ddd) HH:mm') }}
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" wire:click="editVisitMenu({{ $visitMenu->id }})">編集</flux:button>
                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        wire:click="deleteVisitMenu({{ $visitMenu->id }})"
                                        wire:confirm="「{{ $visitMenu->name }}」を削除しますか？"
                                    >
                                        削除
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif

        <flux:error name="visitMenus" />

        <form wire:submit="saveVisitMenu" class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="lg" level="3">{{ $editingVisitMenuId === null ? 'メニューを追加' : 'メニューを編集' }}</flux:heading>

            <flux:input wire:model="visitMenuForm.name" label="メニュー名" type="text" placeholder="一般" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="visitMenuForm.price" label="価格（円）" type="number" min="0" step="1" inputmode="numeric" required />
                <flux:input wire:model="visitMenuForm.capacity" label="定員（名）" type="number" min="1" step="1" inputmode="numeric" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="visitMenuForm.sales_starts_at" label="販売開始" type="datetime-local" required />
                <flux:input wire:model="visitMenuForm.sales_ends_at" label="販売終了" type="datetime-local" required />
            </div>

            <div class="flex justify-end gap-2">
                @if ($editingVisitMenuId !== null)
                    <flux:button variant="ghost" wire:click="cancelVisitMenuEdit">キャンセル</flux:button>
                @endif

                <flux:button variant="primary" type="submit" data-test="save-visit-menu-button">
                    {{ $editingVisitMenuId === null ? '追加' : '更新' }}
                </flux:button>
            </div>
        </form>
    </div>

    <flux:separator />

    <div class="flex items-center justify-between">
        @can('togglePublish', $visit)
            <div>
                <flux:button wire:click="togglePublish" data-test="toggle-publish-button">
                    {{ $visit->isPublished() ? '下書きに戻す' : '公開する' }}
                </flux:button>

                @if (! $visit->isPublished() && $this->visitMenus->isEmpty())
                    <flux:text size="lg" class="mt-2">公開するにはメニューが1つ以上必要です。</flux:text>
                @endif

                <flux:error name="publish" />
            </div>
        @else
            <span></span>
        @endcan

        <flux:modal.trigger name="confirm-visit-deletion">
            <flux:button variant="danger" data-test="delete-visit-button">削除</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:modal name="confirm-visit-deletion" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">慰問を削除しますか？</flux:heading>
                <flux:text size="lg" class="mt-2">「{{ $visit->title }}」を削除します。この操作は取り消せません。</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">キャンセル</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteVisit" data-test="confirm-delete-visit-button">削除する</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
