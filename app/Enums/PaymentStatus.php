<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'Unpaid';
    case PartiallyPaid = 'Partially Paid';
    case FullyPaid = 'Fully Paid';

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Unpaid => 'secondary',
            self::PartiallyPaid => 'warning',
            self::FullyPaid => 'success',
        };
    }
}
