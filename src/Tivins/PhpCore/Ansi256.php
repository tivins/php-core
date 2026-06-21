<?php

declare(strict_types=1);

namespace Tivins\PhpCore;

use InvalidArgumentException;

final class Ansi256
{
    private static bool $enabled = true;

    // Active/désactive le mode ANSI (couleurs + styles)
    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    // Détecte si on est dans un terminal (et pas juste sur un buffer/redirect)
    // Heuristique simple + contrôles d'environnement.
    public static function autoDetect(): void
    {
        $isTty = function_exists('stream_isatty') && @stream_isatty(STDOUT);

        $term = (string)($_SERVER['TERM'] ?? getenv('TERM') ?: '');
        $noColor = getenv('NO_COLOR') !== false;
        $force = getenv('CLICOLOR_FORCE') !== false || getenv('FORCE_COLOR') !== false;

        // Si on ne semble pas être dans un terminal et pas de force explicite → désactiver
        if (!$isTty && !$force) {
            self::$enabled = false;
            return;
        }

        // TERM vide ou "dumb" => désactiver
        if (strtolower($term) === '' || strtolower($term) === 'dumb') {
            self::$enabled = false;
            return;
        }

        // Override via NO_COLOR
        if ($noColor && !$force) {
            self::$enabled = false;
            return;
        }

        self::$enabled = true;
    }

    // Couleurs 0..255 (palette xterm 256)
    public static function fg(int $color): string
    {
        self::assertColor($color);
        return self::$enabled ? "\e[38;5;{$color}m" : '';
    }

    public static function bg(int $color): string
    {
        self::assertColor($color);
        return self::$enabled ? "\e[48;5;{$color}m" : '';
    }

    // Styles
    public static function bold(bool $on): string
    {
        if (!self::$enabled) return '';
        return $on ? "\e[1m" : "\e[22m";
    }

    public static function underline(bool $on): string
    {
        if (!self::$enabled) return '';
        return $on ? "\e[4m" : "\e[24m";
    }

    public static function blink(bool $on): string
    {
        if (!self::$enabled) return '';
        return $on ? "\e[5m" : "\e[25m";
    }

    public static function reset(): string
    {
        return self::$enabled ? "\e[0m" : '';
    }

    // Affichage ligne
    public static function line(
        string $text,
        ?int $fg = null,
        ?int $bg = null,
        bool $bold = false,
        bool $underline = false,
        bool $blink = false
    ): void {
        if (!self::$enabled) {
            echo $text . PHP_EOL;
            return;
        }

        $seq = '';
        if ($bold) $seq .= self::bold(true);
        if ($underline) $seq .= self::underline(true);
        if ($blink) $seq .= self::blink(true);
        if ($fg !== null) $seq .= self::fg($fg);
        if ($bg !== null) $seq .= self::bg($bg);

        echo $seq . $text . self::reset() . PHP_EOL;
    }

    private static function assertColor(int $color): void
    {
        if ($color < 0 || $color > 255) {
            throw new InvalidArgumentException('La couleur doit être comprise entre 0 et 255.');
        }
    }
}