<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case InAuction = 'in_auction';
    case Sold = 'sold';
    case Archived = 'archived';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::InAuction => 'In Auction',
            self::Sold => 'Sold',
            self::Archived => 'Archived',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => in_array($status, [self::Published, self::Archived]),
            self::Published => in_array($status, [self::InAuction, self::Draft, self::Archived]),
            self::InAuction => in_array($status, [self::Sold, self::Archived]),
            self::Sold => in_array($status, [self::Archived]),
            self::Archived => in_array($status, [self::Draft]),
        };
    }
}