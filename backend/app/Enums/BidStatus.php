<?php

namespace App\Enums;

enum BidStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Outbid = 'outbid';
    case Winning = 'winning';
    case Won = 'won';
    case Lost = 'lost';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Outbid => 'Outbid',
            self::Winning => 'Winning',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Accepted, self::Winning]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Won, self::Lost, self::Rejected]);
    }
}