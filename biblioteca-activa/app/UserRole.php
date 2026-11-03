<?php

namespace App;

enum UserRole: string
{
    case Student = 'student';
    case Librarian = 'librarian';
    case Admin = 'admin';

    /**
     * @return list<string>
     */
    public static function creatableByAdmin(): array
    {
        return [
            self::Librarian->value,
            self::Student->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Estudiante',
            self::Librarian => 'Bibliotecario',
            self::Admin => 'Administrador',
        };
    }
}
