<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin     = 'admin';
    case Gallery   = 'gallery';
    case Artist    = 'artist';
    case Collector = 'collector';

    public function label(): string
    {
        return match ($this) {
            self::Admin     => 'Administrator',
            self::Gallery   => 'Gallery',
            self::Artist    => 'Artist',
            self::Collector => 'Collector',
        };
    }

    /** Roles selectable on the public registration form. */
    public static function publicRegisterChoices(): array
    {
        return [self::Gallery, self::Artist, self::Collector];
    }

    /** Roles allowed into the Filament admin panel.
     *  Project Arch: all registered roles have an admin dashboard
     *  (Gallery=full, Artist=scoped, Collector=scoped minus advanced features).
     */
    public function canAccessFilament(): bool
    {
        return in_array($this, [self::Admin, self::Gallery, self::Artist, self::Collector], true);
    }
}
