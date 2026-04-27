<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Utilidades para búsquedas seguras en base de datos.
 */
final class SearchHelper
{
    /**
     * Escapa caracteres especiales de LIKE en MySQL para prevenir
     * wildcard abuse (% _ \) en búsquedas de usuario.
     *
     * Uso: where('name', 'like', '%' . SearchHelper::escapeLike($input) . '%')
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
