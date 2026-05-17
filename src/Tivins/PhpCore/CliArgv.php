<?php
declare(strict_types=1);

namespace Tivins\PhpCore;

use InvalidArgumentException;

/**
 * Analyse portable des arguments CLI (`$argv`), en complément fragile de {@see getopt()} sous Windows
 * (options longues optionnelles, valeurs tableau castées en chaîne « Array », etc.).
 *
 * Les méthodes {@see self::longFlagValue()} et {@see self::shortFlagValue()} lisent explicitement `$argv`
 * plutôt que l’analyseur natif, ce qui stabilise `--nom valeur` et `--nom=valeur`.
 */
final class CliArgv
{
    /**
     * @param list<string> $argv Copie typique : variable globale `$argv` (script en premier élément).
     */
    public function __construct(private readonly array $argv)
    {
        foreach ($this->argv as $i => $arg) {
            if (! is_string($arg)) {
                throw new InvalidArgumentException(sprintf('CliArgv: argv[%s] must be string.', (string) $i));
            }
        }
    }

    /**
     * Construit une instance à partir de la variable globale `$argv` (tableau vide si indisponible, ex. SAPI non-cli).
     */
    public static function fromGlobals(): self
    {
        global $argv;

        if (! isset($argv) || ! is_array($argv)) {
            return new self([]);
        }

        $normalized = [];
        foreach ($argv as $part) {
            $normalized[] = (string) $part;
        }

        return new self(array_values($normalized));
    }

    /** @return list<string> */
    public function raw(): array
    {
        return $this->argv;
    }

    public function script(): ?string
    {
        return $this->argv[0] ?? null;
    }

    /**
     * Valeur scalaire exploitable pour une entrée {@see getopt()} : dernier élément si tableau ;
     * faux / null → chaîne vide (évite le cast `(string)` sur tableau qui produit « Array »).
     */
    public function normalizeGetoptValue(mixed $value): string
    {
        if ($value === false || $value === null) {
            return '';
        }

        if (is_array($value)) {
            $last = end($value);

            return $last === false ? '' : trim((string) $last);
        }

        return trim((string) $value);
    }

    public function hasLongFlag(string $name): bool
    {
        return $this->longFlagValue($name) !== null;
    }

    /**
     * Lit `--name=value` ou `--name valeur` (la valeur ne doit pas commencer par `-`).
     * Retourne `null` si le drapeau est absent, sinon la chaîne (éventuellement vide pour `--name=` ou `--name` sans valeur séparée).
     */
    public function longFlagValue(string $name): ?string
    {
        self::assertLongFlagName($name);

        $flag = '--' . $name;
        $prefix = $flag . '=';

        foreach ($this->argv as $i => $arg) {
            if ($arg === $flag) {
                $next = $this->argv[$i + 1] ?? null;
                if ($next !== null && $next !== '' && ! str_starts_with($next, '-')) {
                    return $next;
                }

                return '';
            }

            if (str_starts_with($arg, $prefix)) {
                return substr($arg, strlen($prefix));
            }
        }

        return null;
    }

    public function hasShortFlag(string $letter): bool
    {
        return $this->shortFlagValue($letter) !== null;
    }

    /**
     * Lit `-x=value` ou `-x valeur` pour un seul caractère identifiant (pas les paquets `-abc`).
     */
    public function shortFlagValue(string $letter): ?string
    {
        if (strlen($letter) !== 1) {
            throw new InvalidArgumentException('Short flag must be exactly one character.');
        }

        $flag = '-' . $letter;
        $prefix = $flag . '=';

        foreach ($this->argv as $i => $arg) {
            if ($arg === $flag) {
                $next = $this->argv[$i + 1] ?? null;
                if ($next !== null && $next !== '' && ! str_starts_with($next, '-')) {
                    return $next;
                }

                return '';
            }

            if (str_starts_with($arg, $prefix)) {
                return substr($arg, strlen($prefix));
            }
        }

        return null;
    }

    /**
     * Sépare les arguments avant et après le premier `--` (opérandes après `--`).
     *
     * @return array{before: list<string>, after: list<string>}
     */
    public function splitDoubleDash(): array
    {
        foreach ($this->argv as $index => $arg) {
            if ($arg === '--') {
                return [
                    'before' => array_values(array_slice($this->argv, 0, $index)),
                    'after' => array_values(array_slice($this->argv, $index + 1)),
                ];
            }
        }

        return ['before' => $this->argv, 'after' => []];
    }

    private static function assertLongFlagName(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('Long flag name must not be empty.');
        }

        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/', $name)) {
            throw new InvalidArgumentException(
                'Long flag name must start with alphanumeric and contain only letters, digits, underscore or hyphen.'
            );
        }
    }
}
