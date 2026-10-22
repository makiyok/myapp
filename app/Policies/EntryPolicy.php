<?php

namespace App\Policies;

use App\Models\Entry;
use App\Models\User;

class EntryPolicy
{
    /**
     * 申し込み者本人、その慰問の福祉施設、管理者。
     */
    public function view(User $user, Entry $entry): bool
    {
        return $user->id === $entry->user_id
            || $user->id === $entry->visit->facility_id
            || $user->isAdmin();
    }

    /**
     * 支払いの中断は申し込み者本人だけ。福祉施設や管理者は他人の申し込みを取り消せない。
     */
    public function cancel(User $user, Entry $entry): bool
    {
        return $user->id === $entry->user_id;
    }
}
