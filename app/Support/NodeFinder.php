<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class NodeFinder
{
    /**
     * Get the absolute path to the Node.js executable.
     *
     * @return string
     */
    public static function getPath(): string
    {
        // 1. Check .env override
        $override = config('scraper.node_path_override');
        if ($override && self::isValid($override)) {
            return $override;
        }

        // 2. Discover based on OS
        $os = PHP_OS_FAMILY;
        $path = $os === 'Windows' ? self::findOnWindows() : self::findOnLinux();

        if ($path) {
            return $path;
        }

        // 3. Last resort fallback to 'node'
        return 'node';
    }

    /**
     * Find Node on Windows systems.
     */
    protected static function findOnWindows(): ?string
    {
        $potentialPaths = [
            // Check global PATH via 'where'
            self::runWhereCommand(),
            
            // Common default paths
            'C:\Program Files\nodejs\node.exe',
            'C:\Program Files (x86)\nodejs\node.exe',
            
            // NVM for Windows (Standard locations)
            'C:\nvm4w\nodejs\node.exe',
            getenv('APPDATA') . '\nvm\nodejs\node.exe',
            getenv('USERPROFILE') . '\AppData\Roaming\nvm\nodejs\node.exe',
        ];

        foreach (array_filter($potentialPaths) as $path) {
            if (self::isValid($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Find Node on Linux/Unix systems.
     */
    protected static function findOnLinux(): ?string
    {
        $potentialPaths = [
            // Check global PATH via 'which'
            self::runWhichCommand(),
            
            // Standard Linux paths
            '/usr/bin/node',
            '/usr/local/bin/node',
            '/opt/node/bin/node',
        ];

        foreach (array_filter($potentialPaths) as $path) {
            if (self::isValid($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Validate if a path is a working Node.js executable.
     */
    public static function isValid(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        // Basic existence check
        if (! file_exists($path) && $path !== 'node') {
            return false;
        }

        try {
            // Execution check
            $output = [];
            $status = -1;
            $escapedPath = escapeshellarg($path);
            
            // Run node --version
            exec("{$escapedPath} --version 2>&1", $output, $status);

            if ($status === 0 && ! empty($output) && str_starts_with(trim($output[0]), 'v')) {
                return true;
            }
        } catch (\Exception $e) {
            Log::debug("Node validation failed for {$path}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Run 'where node' on Windows.
     */
    protected static function runWhereCommand(): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $output = [];
        $status = -1;
        exec('where node 2>nul', $output, $status);

        return $status === 0 && ! empty($output) ? trim($output[0]) : null;
    }

    /**
     * Run 'which node' on Linux.
     */
    protected static function runWhichCommand(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return null;
        }

        $output = [];
        $status = -1;
        exec('which node 2>/dev/null', $output, $status);

        return $status === 0 && ! empty($output) ? trim($output[0]) : null;
    }
}
