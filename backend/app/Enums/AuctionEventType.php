<?php

namespace App\Enums;

enum AuctionEventType: string
{
    case Created = 'created';
    case Started = 'started';
    case BidPlaced = 'bid_placed';
    case BidOutbid = 'bid_outbid';
    case ReserveMet = 'reserve_met';
    case Extended = 'extended';
    case Ended = 'ended';
    case Cancelled = 'cancelled';
    case WinnerDeclared = 'winner_declared';
    case PaymentInitiated = 'payment_initiated';
    case Completed = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Auction Created',
            self::Started => 'Auction Started',
            self::BidPlaced => 'Bid Placed',
            self::BidOutbid => 'Outbid',
            self::ReserveMet => 'Reserve Price Met',
            self::Extended => 'Auction Extended',
            self::Ended => 'Auction Ended',
            self::Cancelled => 'Auction Cancelled',
            self::WinnerDeclared => 'Winner Declared',
            self::PaymentInitiated => 'Payment Initiated',
            self::Completed => 'Auction Completed',
        };
    }
}