<?php

namespace App;

enum UserRole: string
{
    case Student = 'student';
    case Librarian = 'librarian';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Estudiante',
            self::Librarian => 'Bibliotecario',
            self::Admin => 'Administrador',
        };
    }
}
