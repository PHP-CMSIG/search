<?php

declare(strict_types=1);

namespace App;

final class Environment
{
    public const DEV = 'dev';
    public const TEST = 'test';
    public const PROD = 'prod';

    public const ENVIRONMENTS = [
        self::DEV,
        self::TEST,
        self::PROD,
    ];

    private static array $values = [];

    public static function prepare(): void
    {
        self::setEnvironment();
        self::setNonEmptyStringOrNull('ALGOLIA_DSN', null);
        self::setBoolean('APP_C3', false);
        self::setBoolean('APP_DEBUG', false);
        self::setNonEmptyStringOrNull('APP_HOST_PATH', null);
    }

    /**
     * @return non-empty-string
     */
    public static function appEnv(): string
    {
        /** @var non-empty-string */
        return self::$values['APP_ENV'];
    }

    public static function isDev(): bool
    {
        return self::DEV === self::appEnv();
    }

    public static function isTest(): bool
    {
        return self::TEST === self::appEnv();
    }

    public static function isProd(): bool
    {
        return self::PROD === self::appEnv();
    }

    /**
     * @return non-empty-string|null
     */
    public static function appHostPath(): string|null
    {
        /** @var non-empty-string|null */
        return self::$values['APP_HOST_PATH'];
    }

    public static function appC3(): bool
    {
        /** @var bool */
        return self::$values['APP_C3'];
    }

    public static function appDebug(): bool
    {
        /** @var bool */
        return self::$values['APP_DEBUG'];
    }

    private static function setEnvironment(): void
    {
        $environment = self::getRawValue('APP_ENV') ?? 'test';

        if (!\in_array($environment, self::ENVIRONMENTS, true)) {
            if (null === $environment) {
                $message = 'APP_ENV environment variable is empty.';
            } else {
                $message = \sprintf('APP_ENV="%s" environment is invalid.', $environment);
            }

            $message .= \sprintf(' Valid values are "%s".', \implode('", "', self::ENVIRONMENTS));

            throw new \RuntimeException($message);
        }
        self::$values['APP_ENV'] = $environment;
    }

    private static function setBoolean(string $key, bool $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = null === $value
            ? $default
            : (\filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE) ?? $default);
    }

    private static function setInteger(string $key, int $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = null === $value ? $default : (int) $value;
    }

    private static function setString(string $key, string $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = $value ?? $default;
    }

    private static function setNonEmptyStringOrNull(string $key, string|null $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = null === $value || '' === $value ? $default : $value;
    }

    private static function getRawValue(string $key): string|null
    {
        $value = \getenv($key, true);
        if (false !== $value) {
            return $value;
        }

        $value = \getenv($key);
        if (false !== $value) {
            return $value;
        }

        return isset($_ENV[$key]) ? (string) $_ENV[$key] : null;
    }
}
