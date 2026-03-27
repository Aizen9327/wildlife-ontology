<?php
declare(strict_types=1);

namespace App\Models;

class OntologySession
{
    private const KEY = 'owl_file_path';

    public static function setFilePath(string $path): void
    {
        $_SESSION[self::KEY] = $path;
    }

    public static function getFilePath(): ?string
    {
        return $_SESSION[self::KEY] ?? null;
    }

    public static function hasFile(): bool
    {
        $path = self::getFilePath();
        return $path !== null && file_exists($path);
    }

    public static function clear(): void
    {
        unset($_SESSION[self::KEY]);
    }

    public static function getModel(): ?OntologyModel
    {
        if (!self::hasFile()) return null;
        try {
            return new OntologyModel(self::getFilePath());
        } catch (\Throwable $e) {
            return null;
        }
    }
}
