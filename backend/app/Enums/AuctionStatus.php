<?php

namespace App\Enums;

enum AuctionStatus: string
{
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Unsold = 'unsold';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Unsold => 'Unsold',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Unsold]);
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Scheduled => in_array($status, [self::Active, self::Cancelled]),
            self::Active => in_array($status, [self::Completed, self::Cancelled, self::Unsold]),
            self::Completed => false,
            self::Cancelled => false,
            self::Unsold => false,
        };
    }
}