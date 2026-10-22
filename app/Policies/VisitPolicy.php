<?php

namespace App\Policies;

use App\Enums\VisitStatus;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Auth\Access\Response;

class VisitPolicy
{
    /**
     * 公開中なら誰でも。下書きと非公開は所有者と管理者だけで、それ以外には存在を隠す（404）。
     */
    public function view(?User $user, Visit $visit): Response
    {
        if ($visit->isPublished()) {
            return Response::allow();
        }

        if ($user !== null && ($user->isAdmin() || $this->owns($user, $visit))) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->isFacility();
    }

    public function update(User $user, Visit $visit): bool
    {
        return $this->owns($user, $visit);
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $this->owns($user, $visit);
    }

    /**
     * 下書きと公開中の切り替え。管理者が非公開にした慰問は所有者にも戻せない。
     */
    public function togglePublish(User $user, Visit $visit): bool
    {
        return $this->owns($user, $visit) && $visit->status !== VisitStatus::Unpublished;
    }

    /**
     * 問題のある慰問を非公開にできるのは管理者だけ。
     */
    public function unpublish(User $user, Visit $visit): bool
    {
        return $user->isAdmin();
    }

    private function owns(User $user, Visit $visit): bool
    {
        return $user->id === $visit->facility_id;
    }
}
