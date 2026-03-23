<?php

declare(strict_types=1);

/*
 * This file is part of the CMS-IG SEAL project.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CmsIg\Seal\Marshaller;

/**
 * @internal this class currently can be modified or removed at any time
 */
final class Flattener
{
    /**
     * @param non-empty-string $metadataKey
     * @param non-empty-string $fieldSeparator
     * @param non-empty-string $metadataSeparator
     * @param non-empty-string $metadataPlaceholder
     */
    public function __construct(
        private readonly string $metadataKey = 's_metadata',
        private readonly string $fieldSeparator = '.',
        private readonly string $metadataSeparator = '/',
        private readonly string $metadataPlaceholder = '*',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function flatten(array $data): array
    {
        $flattenData = $this->doFlatten($data);

        $newData = [];
        $metadata = [];
        foreach ($flattenData as $key => $value) {
            unset($flattenData[$key]);

            /** @var string $metadataKey */
            $metadataKey = \preg_replace('/' . \preg_quote($this->metadataSeparator, '/') . '(\d+)' . \preg_quote($this->metadataSeparator, '/') . '/', $this->metadataSeparator, $key, -1);
            /** @var string $metadataKey */
            $metadataKey = \preg_replace('/' . \preg_quote($this->metadataSeparator, '/') . '(\d+)$/', '', $metadataKey, -1);
            $newKey = \str_replace($this->metadataSeparator, $this->fieldSeparator, $metadataKey);

            if ($newKey === $key) {
                $newData[$newKey] = $value;

                continue;
            }

            if ($metadataKey === $key) {
                $newData[$newKey] = $value;
                $newValue = [$value];
            } else {
                $newValue = \is_array($value) ? $value : [$value];
                $oldValue = ($newData[$newKey] ?? []);

                \assert(\is_array($oldValue), 'Expected old value of key "' . $newKey . '" to be an array got "' . \get_debug_type($oldValue) . '".');

                $newData[$newKey] = [
                    ...$oldValue,
                    ...$newValue,
                ];
            }

            if (\str_contains($metadataKey, $this->metadataSeparator)) {
                foreach ($newValue as $v) {
                    $metadata[$metadataKey][] = \preg_replace_callback('/[^' . \preg_quote($this->metadataSeparator, '/') . ']+/', fn ($matches) => \is_numeric($matches[0]) ? $matches[0] : $this->metadataPlaceholder, $key);
                }
            }
        }

        if ([] !== $metadata) {
            $newData[$this->metadataKey] = \json_encode($metadata, \JSON_THROW_ON_ERROR);
        }

        return $newData;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function doFlatten(array $data, string $prefix = ''): array
    {
        $newData = [];
        foreach ($data as $key => $value) {
            if (!\is_array($value)
                || [] === $value
            ) {
                $newData[$prefix . $key] = $value;

                continue;
            }

            $flattened = $this->doFlatten($value, $key . $this->metadataSeparator); // @phpstan-ignore-line argument.type
            foreach ($flattened as $subKey => $subValue) {
                $newData[$prefix . $subKey] = $subValue;
            }
        }

        return $newData;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function unflatten(array $data): array
    {
        /** @var array<string, mixed> $newData */
        $newData = [];
        /** @var array<string, array<string>> $metadata */
        $metadata = [];
        /** @var array<string, string> $metadataKeyMapping */
        $metadataKeyMapping = [];
        if (\array_key_exists($this->metadataKey, $data)) {
            \assert(\is_string($data[$this->metadataKey]), 'Expected metadata to be a string.');

            /** @var array<string, array<string>> $metadata */
            $metadata = \json_decode($data[$this->metadataKey], true, flags: \JSON_THROW_ON_ERROR);

            foreach (\array_keys($metadata) as $subMetadataKey) {
                $metadataKeyMapping[\str_replace($this->metadataSeparator, $this->fieldSeparator, $subMetadataKey)] = $subMetadataKey;
            }

            unset($data[$this->metadataKey]);
        }

        foreach ($data as $key => $value) {
            $metadataKey = $metadataKeyMapping[$key] ?? null;
            if (null === $metadataKey) {
                $newData[$key] = $value;

                continue;
            }

            /** @var string[] $keyParts */
            $keyParts = \explode($this->metadataSeparator, $metadataKey);
            if (!\is_array($value)) {
                $value = [$value];
            }

            foreach ($value as $subKey => $subValue) {
                \assert(\array_key_exists($subKey, $metadata[$metadataKey]), 'Expected key "' . $subKey . '" to exist in "' . $key . '".');

                /** @var string[] $keyPartsReplacements */
                $keyPartsReplacements = $keyParts;

                /** @var string $newKeyPath */
                $newKeyPath = \preg_replace_callback('/' . \preg_quote($this->metadataPlaceholder, '/') . '/', static function () use (&$keyPartsReplacements) {  // @phpstan-ignore-line argument.type
                    return \array_shift($keyPartsReplacements);
                }, $metadata[$metadataKey][$subKey]);

                /** @var array<string, mixed> $newSubData */
                $newSubData = &$newData;
                foreach (\explode($this->metadataSeparator, $newKeyPath) as $newKeyPart) {
                    $newSubData = &$newSubData[$newKeyPart]; // @phpstan-ignore-line
                }

                /** @var array<string, mixed> $newSubData */
                $newSubData = $subValue;
            }
        }

        return $newData;
    }
}
