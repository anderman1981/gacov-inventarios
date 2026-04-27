<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case CONTADOR = 'contador';
    case CONDUCTOR = 'conductor';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::MANAGER => 'Manager',
            self::CONTADOR => 'Contador',
            self::CONDUCTOR => 'Conductor',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string, string> Mapa valor => etiqueta para selects */
    public static function labelsMap(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = $case->label();
        }

        return $map;
    }

    /**
     * Roles que un admin de tenant puede asignar.
     * super_admin solo puede ser asignado por otro super_admin.
     *
     * @return list<string>
     */
    public static function tenantAssignable(): array
    {
        return [
            self::ADMIN->value,
            self::MANAGER->value,
            self::CONTADOR->value,
            self::CONDUCTOR->value,
        ];
    }
}
