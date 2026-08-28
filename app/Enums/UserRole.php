<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'ADMIN';
    case Viewer = 'VIEWER';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
