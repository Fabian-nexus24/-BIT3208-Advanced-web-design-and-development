<?php
declare(strict_types=1);

/**
 * Load optional environment overrides from config/env.local.php
 *
 * @return array<string, mixed>
 */
function app_env(): array
{
    static $env = null;
    if ($env !== null) {
        return $env;
    }

    // Auto-detect env.local.php in root config/ (Laragon) or WEEK 1/config/ (GitHub)
    $appRoot = dirname(__DIR__);
    if (is_file($appRoot . '/config/env.local.php')) {
        $path = $appRoot . '/config/env.local.php';
    } else {
        $path = $appRoot . '/WEEK 1/config/env.local.php';
    }
    $env = is_file($path) ? (require $path) : [];
    if (!is_array($env)) {
        $env = [];
    }
    return $env;
}

function env_string(string $key, string $default = ''): string
{
    $val = app_env()[$key] ?? $default;
    return is_string($val) ? $val : $default;
}

function env_bool(string $key, bool $default = false): bool
{
    $val = app_env()[$key] ?? $default;
    if (is_bool($val)) {
        return $val;
    }
    if (is_string($val)) {
        return in_array(strtolower($val), ['1', 'true', 'yes', 'on'], true);
    }
    return $default;
}

/**
 * Build BASE_URL from the current HTTP request (supports ngrok / reverse proxies).
 */
function detect_base_url(): string
{
    if (PHP_SAPI === 'cli') {
        return env_string('BASE_URL', 'http://localhost/farmconnect/');
    }

    $scheme = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    ) {
        $scheme = 'https';
    }

    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = trim(explode(',', (string) $host)[0]);

    $appFolder = basename(dirname(__DIR__));
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));

    $marker = '/' . $appFolder . '/';
    $pos = stripos($script, $marker);
    if ($pos !== false) {
        return $scheme . '://' . $host . substr($script, 0, $pos + strlen($marker));
    }

    $dir = rtrim(dirname($script), '/');
    if ($dir !== '' && $dir !== '/' && basename($dir) === $appFolder) {
        return $scheme . '://' . $host . $dir . '/';
    }

    if ($dir === '' || $dir === '/' || $dir === '.') {
        return $scheme . '://' . $host . '/';
    }

    return $scheme . '://' . $host . $dir . '/';
}

function resolve_base_url(): string
{
    $configured = env_string('BASE_URL', '');
    $auto = env_bool('AUTO_BASE_URL', true);

    if (PHP_SAPI === 'cli' || !$auto) {
        return $configured !== '' ? $configured : 'http://localhost/farmconnect/';
    }

    return detect_base_url();
}
