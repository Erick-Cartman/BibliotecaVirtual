<?php

namespace App;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Cancelled = 'cancelled';
    case Fulfilled = 'fulfilled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Ready => 'Lista para recoger',
            self::Cancelled => 'Cancelada',
            self::Fulfilled => 'Atendida',
            self::Expired => 'Vencida',
        };
    }
}
