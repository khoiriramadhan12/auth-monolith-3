<?php

namespace Modules\Shared\Exceptions;

use Exception;

class MemberNotActiveException extends Exception
{
    public function __construct(string $memberCode)
    {
        parent::__construct("Anggota dengan kode '{$memberCode}' tidak dalam status aktif atau tidak berhak meminjam.");
    }
}
