<?php

namespace App\Fournisseur\Domain\Enum;

enum FournisseurStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
