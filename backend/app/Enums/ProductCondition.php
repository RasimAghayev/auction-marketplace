<?php

namespace App\Enums;

enum ProductCondition: string
{
    case New = 'new';
    case Used = 'used';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Used => 'Used',
        };
    }
}