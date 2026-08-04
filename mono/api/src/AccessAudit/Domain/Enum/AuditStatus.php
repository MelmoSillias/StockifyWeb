<?php

namespace App\AccessAudit\Domain\Enum;

enum AuditStatus: string
{
    case Success = 'success';
    case Failure = 'failure';
}
