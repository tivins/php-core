<?php
declare(strict_types=1);

namespace Tivins\PhpCore;

use Exception;

class DotEnv
{
    /**
     * Motif d'un nom de variable d'environnement valide.
     * Empêche d'injecter des clés exotiques (espaces, `=`, octets nuls, etc.) via putenv().
     */
    private const KEY_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    /**
     * Charge les variables d'environnement depuis un fichier.
     *
     * Par défaut, les variables déjà définies dans l'environnement ne sont PAS écrasées
     * (`$overwrite = false`) : un fichier `.env` ne peut donc pas clobber `PATH`, `LD_PRELOAD`
     * ou toute autre variable héritée du système / du parent.
     *
     * @throws Exception si le fichier n'existe pas
     */
    public static function loadFile(string $filename, bool $overwrite = false): void
    {
        if (!file_exists($filename)) {
            throw new Exception("File not found: $filename");
        }
        $lines = file($filename);
        if ($lines === false) {
            throw new Exception("Cannot read file: $filename");
        }
        self::loadLines($lines, $overwrite);
    }

    public static function tryLoadFile(string $filename, bool $overwrite = false): void
    {
        try { self::loadFile($filename, $overwrite); }
        catch (Exception) {}
    }

    /**
     * Charge des paires `clé=valeur` depuis un tableau de lignes.
     *
     * - Les lignes vides et les commentaires (`#`) sont ignorés.
     * - Le préfixe `export ` est toléré.
     * - Les noms de clés invalides sont ignorés (voir {@see KEY_PATTERN}).
     * - Les valeurs sont trim ; les guillemets simples/doubles entourants sont retirés
     *   (les séquences `\n \r \t \" \\` sont interprétées dans les guillemets doubles) ;
     *   un commentaire en fin de ligne (` # ...`) est retiré pour les valeurs non quotées.
     * - Si `$overwrite` est faux, une clé déjà présente dans l'environnement est conservée.
     *
     * @param array<int, string> $lines
     */
    public static function loadLines(array $lines, bool $overwrite = false): void
    {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = ltrim(substr($line, strlen('export ')));
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $rawValue] = explode('=', $line, 2);
            $key = trim($key);
            if (preg_match(self::KEY_PATTERN, $key) !== 1) {
                continue;
            }
            if (!$overwrite && self::isDefined($key)) {
                continue;
            }
            $value = self::parseValue($rawValue);
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }

    private static function isDefined(string $key): bool
    {
        return array_key_exists($key, $_ENV)
            || array_key_exists($key, $_SERVER)
            || getenv($key) !== false;
    }

    private static function parseValue(string $rawValue): string
    {
        $value = trim($rawValue);
        if ($value === '') {
            return '';
        }

        $quote = $value[0];
        if (($quote === '"' || $quote === "'") && strlen($value) >= 2 && $value[strlen($value) - 1] === $quote) {
            $inner = substr($value, 1, -1);
            if ($quote === '"') {
                return strtr($inner, [
                    '\\n' => "\n",
                    '\\r' => "\r",
                    '\\t' => "\t",
                    '\\"' => '"',
                    '\\\\' => '\\',
                ]);
            }
            return $inner;
        }

        // Valeur non quotée : retire un commentaire en fin de ligne précédé d'un espace.
        $stripped = preg_replace('/\s+#.*$/', '', $value);

        return rtrim($stripped ?? $value);
    }
}
