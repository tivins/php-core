<?php

declare(strict_types=1);

namespace Tivins\PhpCore;

use InvalidArgumentException;

final class Ansi256
{
    // Couleurs 0..255 (palette xterm 256)
    public static function fg(int $color): string
    {
        self::assertColor($color);
        return "\e[38;5;{$color}m";
    }

    public static function bg(int $color): string
    {
        self::assertColor($color);
        return "\e[48;5;{$color}m";
    }

    // Styles
    public static function bold(bool $on = true): string
    {
        return $on ? "\e[1m" : "\e[22m";
    }

    public static function underline(bool $on = true): string
    {
        return $on ? "\e[4m" : "\e[24m";
    }

    public static function blink(bool $on = true): string
    {
        return $on ? "\e[5m" : "\e[25m";
    }

    public static function reset(): string
    {
        return "\e[0m";
    }

    // Affichage
    public static function line(
        string $text,
        ?int $fg = null,
        ?int $bg = null,
        bool $bold = false,
        bool $underline = false,
        bool $blink = false
    ): void {
        $seq = '';

        if ($bold) {
            $seq .= self::bold(true);
        }
        if ($underline) {
            $seq .= self::underline(true);
        }
        if ($blink) {
            $seq .= self::blink(true);
        }
        if ($fg !== null) {
            $seq .= self::fg($fg);
        }
        if ($bg !== null) {
            $seq .= self::bg($bg);
        }

        echo $seq . $text . self::reset() . PHP_EOL;
    }

    private static function assertColor(int $color): void
    {
        if ($color < 0 || $color > 255) {
            throw new InvalidArgumentException('La couleur doit être comprise entre 0 et 255.');
        }
    }
}