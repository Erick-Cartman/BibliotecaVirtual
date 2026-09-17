<?php

namespace App;

enum CopyStatus: string
{
    case Available = 'available';
    case Loaned = 'loaned';
    case Reserved = 'reserved';
    case Maintenance = 'maintenance';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Loaned => 'Prestado',
            self::Reserved => 'Reservado',
            self::Maintenance => 'En revisión',
            self::Retired => 'Baja',
        };
    }
}
