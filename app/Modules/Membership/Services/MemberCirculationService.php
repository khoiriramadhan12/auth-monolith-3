<?php

namespace Modules\Membership\Services;

use Modules\Membership\Models\Member;
use Modules\Shared\Contracts\MemberCirculationServiceInterface;

class MemberCirculationService implements MemberCirculationServiceInterface
{
    public function getMemberForBorrowing(int $memberId): ?array
    {
        $member = Member::find($memberId);
        if (!$member) {
            return null;
        }

        return [
            'id' => $member->id,
            'member_code' => $member->member_code,
            'name' => $member->name,
            'email' => $member->email,
            'status' => $member->status,
            'is_active' => $member->isActive(),
        ];
    }

    public function canBorrow(int $memberId): bool
    {
        $member = Member::find($memberId);
        return $member !== null && $member->isActive();
    }

    public function updateBorrowingStatus(int $memberId, string $status): bool
    {
        $member = Member::find($memberId);
        if (!$member) {
            return false;
        }

        $member->update(['status' => $status]);
        return true;
    }
}
