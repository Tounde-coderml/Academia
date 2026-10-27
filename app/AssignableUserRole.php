<?php

namespace App;

enum AssignableUserRole: string
{
    case Student = 'etudiant';
    case ClassManager = 'responsable_classe';
    case Administration = 'administration';
    case Administrator = 'administrateur';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Étudiant',
            self::ClassManager => 'Responsable de classe',
            self::Administration => 'Administration',
            self::Administrator => 'Administrateur',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases(),
        );
    }
}
