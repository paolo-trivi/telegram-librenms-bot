<?php

namespace LibreBot\Lib;

/**
 * Version Management
 * Centralized version control for LibreBot
 */
class Version
{
    private const MAJOR = 2;
    private const MINOR = 0;
    private const PATCH = 1;
    private const RELEASE_DATE = '2025-12-29';

    /**
     * Get full version string
     */
    public static function get(): string
    {
        return self::MAJOR . '.' . self::MINOR . '.' . self::PATCH;
    }

    /**
     * Get version with prefix
     */
    public static function getFull(): string
    {
        return 'v' . self::get();
    }

    /**
     * Get release date
     */
    public static function getReleaseDate(): string
    {
        return self::RELEASE_DATE;
    }

    /**
     * Get formatted version info
     */
    public static function getInfo(): string
    {
        return sprintf(
            "LibreBot %s (Released: %s)",
            self::getFull(),
            self::RELEASE_DATE
        );
    }

    /**
     * Check if version is compatible
     */
    public static function isCompatible(string $requiredVersion): bool
    {
        return version_compare(self::get(), $requiredVersion, '>=');
    }
}
