<?php

namespace Modules\Shared\Contracts;

interface MemberCirculationServiceInterface
{
    public function getMemberForBorrowing(int $memberId): ?array;
    public function canBorrow(int $memberId): bool;
    public function updateBorrowingStatus(int $memberId, string $status): bool;
}
