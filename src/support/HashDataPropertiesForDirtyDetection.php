<?php

namespace Raxm\Support;

interface HydrationMiddleware
{
    public static function hydrate($instance, $request);
    public static function dehydrate($instance, $response);
}

class HashDataPropertiesForDirtyDetection implements HydrationMiddleware
{
    protected static $propertyHashesByComponentId = [];

    public static function hydrate($instance, $request)
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

    public static function dehydrate($instance, $response)
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

    public static function rehashProperty($name, $value, $component)
    {
        static::$propertyHashesByComponentId[$component->id][$name] = static::hash($value);
    }

    public static function hash($value)
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

    private static function flattenArrayWithPrefix($array, $prefix)
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
