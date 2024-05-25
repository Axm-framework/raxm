<?php

namespace Raxm\Support;

/**
 * Interface HydrationMiddleware
 *
 * Defines methods for hydrating and dehydrating instances.
 * @package Raxm\Support
 */
interface HydrationMiddleware
{
    /**
     * Hydrates the given instance with data from the request.
     */
    public static function hydrate(object $instance, array $request);

    /**
     * Dehydrates the given instance with data from the response.
     */
    public static function dehydrate(object $instance, array $response);
}

/**
 * Class HashDataPropertiesForDirtyDetection
 *
 * Implements the HydrationMiddleware interface to hash and track property changes for dirty detection.
 * @package Raxm\Support
 */
class HashDataPropertiesForDirtyDetection implements HydrationMiddleware
{
    /**
     * Holds property hashes indexed by component ID.
     */
    protected static array $propertyHashesByComponentId = [];

    /**
     * Hydrates the instance with data from the request.
     */
    public static function hydrate(object $instance, array $request)
    {
        $data = dataGet($request, 'memo.data', []);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach (self::flattenArrayWithPrefix($value, $key . '.') as $dottedKey => $value) {
                    self::rehashProperty($dottedKey, $value, $instance);
                }
            } else {
                static::rehashProperty($key, $value, $instance);
            }
        }
    }

    /**
     * Dehydrates the instance with data from the response and tracks dirty properties.
     */
    public static function dehydrate(object $instance, array $response)
    {
        $data = dataGet($response, 'memo.data', []);

        $dirtyProps = [];

        if (isset(static::$propertyHashesByComponentId[$instance->id])) {
            foreach (static::$propertyHashesByComponentId[$instance->id] as $key => $hash) {
                $value = dataGet($data, $key);

                if (static::hash($value) !== $hash) {
                    $dirtyProps[] = $key;
                }
            }
        }

        dataSet($response, 'effects.dirty', $dirtyProps);
    }

    /**
     * Rehashes a specific property of the component.
     */
    public static function rehashProperty(string $name, $value, object $component)
    {
        static::$propertyHashesByComponentId[$component->id][$name] = static::hash($value);
    }

    /**
     * Hashes a value to a unique identifier.
     */
    public static function hash(mixed $value): int|string
    {
        if (!is_null($value) && !is_string($value) && !is_numeric($value) && !is_bool($value)) {
            if (is_array($value)) {
                return json_encode($value);
            }

            $value = method_exists($value, '__toString')
                ? (string) $value
                : json_encode($value);
        }

        return crc32($value ?? '');
    }

    /**
     * Recursively flattens a multidimensional array with a given prefix.
     */
    private static function flattenArrayWithPrefix(array $array, string $prefix): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flattenArrayWithPrefix($value, $prefix . $key . '.'));
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }
}
