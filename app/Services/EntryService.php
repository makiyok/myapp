<?php

namespace App\Services;

use App\Enums\EntryStatus;
use App\Exceptions\PurchaseException;
use App\Models\Entry;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitMenu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EntryService
{
    /**
     * 支払い待ちの申し込みが枠を押さえておける時間（分）
     */
    public const int PENDING_MINUTES = 15;

    /**
     * 1回の申し込みで買える、メニューごとの人数の上限
     */
    public const int MAX_QUANTITY = 10;

    /**
     * 支払い待ちの申し込みと明細を作る。金額は受け取らず、メニューの現在の価格から決める。
     *
     * 人数は画面から書き換えられる値なので、型も範囲もここで確認する。
     *
     * @param  array<array-key, mixed>  $quantities  メニューの ID => 人数
     *
     * @throws PurchaseException
     */
    public function createEntry(User $user, Visit $visit, array $quantities): Entry
    {
        return DB::transaction(function () use ($user, $visit, $quantities): Entry {
            if (! $visit->isPublished()) {
                throw new PurchaseException('この慰問は現在申し込みできません。');
            }

            if ($quantities === []) {
                throw new PurchaseException('メニューと人数を選んでください。');
            }

            $visitMenus = $visit->visitMenus()->whereKey(array_keys($quantities))->get()->keyBy('id');

            $items = [];

            foreach ($quantities as $visitMenuId => $quantity) {
                $visitMenu = $visitMenus->get($visitMenuId);

                if ($visitMenu === null) {
                    throw new PurchaseException('選択したメニューが見つかりません。');
                }

                $quantity = $this->validQuantity($quantity);

                $this->ensurePurchasable($visitMenu, $quantity);

                $items[] = [
                    'visit_menu_id' => $visitMenu->id,
                    'quantity' => $quantity,
                    'unit_price' => $visitMenu->price,
                ];
            }

            $entry = $user->entries()->create([
                'visit_id' => $visit->id,
                'total_amount' => array_sum(array_map(
                    fn (array $item): int => $item['unit_price'] * $item['quantity'],
                    $items,
                )),
            ]);

            $entry->items()->createMany($items);

            return $entry;
        });
    }

    /**
     * 支払い待ちの申し込みを支払い済みにし、明細の人数分の参加証を発行する。
     *
     * Webhook と完了ページが同時に来ても二重にならないよう、status が pending の行だけを更新する。
     * 更新できたほう（1件だけ）が参加証を発行する。
     *
     * @return bool 今回の呼び出しで支払い済みにしたら true、すでに処理済みなら false
     */
    public function markAsPaid(Entry $entry, ?string $paymentIntentId = null): bool
    {
        return DB::transaction(function () use ($entry, $paymentIntentId): bool {
            $updated = Entry::query()
                ->whereKey($entry->id)
                ->where('status', EntryStatus::Pending)
                ->update([
                    'status' => EntryStatus::Paid,
                    'paid_at' => now(),
                    'payment_intent_id' => $paymentIntentId,
                ]);

            if ($updated === 0) {
                return false;
            }

            foreach ($entry->items()->get() as $item) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $item->passes()->create(['code' => (string) Str::uuid()]);
                }
            }

            $entry->refresh();

            return true;
        });
    }

    /**
     * @throws PurchaseException
     */
    private function validQuantity(mixed $quantity): int
    {
        $isInteger = is_int($quantity) || (is_string($quantity) && ctype_digit($quantity));

        if (! $isInteger || (int) $quantity < 1 || (int) $quantity > self::MAX_QUANTITY) {
            throw new PurchaseException('人数は1名から'.self::MAX_QUANTITY.'名の間で選んでください。');
        }

        return (int) $quantity;
    }

    /**
     * @throws PurchaseException
     */
    private function ensurePurchasable(VisitMenu $visitMenu, int $quantity): void
    {
        if (! $visitMenu->isOnSale()) {
            throw new PurchaseException("「{$visitMenu->name}」は販売期間外です。");
        }

        $remaining = $visitMenu->remaining();

        if ($quantity > $remaining) {
            throw new PurchaseException("「{$visitMenu->name}」の残り人数が足りません（残り{$remaining}名）。");
        }
    }
}
