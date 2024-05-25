<?php

namespace Axm\Raxm;

class ComponentProperties
{
    /**
     * Get public properties defined by the subclass.
     */
    public static function getPublicPropertiesDefinedBySubClass(object $instance): array
    {
        $publicProperties = array_filter((new \ReflectionObject($instance))->getProperties(), function ($property) {
            return $property->isPublic() && !$property->isStatic();
        });

        $data = [];

        foreach ($publicProperties as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $data[$property->getName()] = self::getInitializedPropertyValue($instance, $property);
            }
        }

        return $data;
    }

    /**
     * Get protected or private properties defined by the subclass.
     */
    public static function getProtectedOrPrivatePropertiesDefinedBySubClass(object $instance): array
    {
        $properties = (new \ReflectionObject($instance))->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);
        $data = [];

        foreach ($properties as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $property->setAccessible(true);
                $data[$property->getName()] = self::getInitializedPropertyValue($instance, $property);
            }
        }

        return $data;
    }

    /**
     * Get the initialized property value.
     */
    public static function getInitializedPropertyValue(object $instance, \ReflectionProperty $property)
    {
        if (method_exists($property, 'isInitialized') && !$property->isInitialized($instance)) {
            return null;
        }

        return $property->getValue($instance);
    }

    /**
     * Check if the class has a specific property.
     */
    public static function hasProperty(object $instance, string $property): bool
    {
        return property_exists($instance, $property);
    }

    /**
     * Set the value of a specific property.
     */
    public static function setPropertyValue(object $instance, string $name, mixed $value): mixed
    {
        return $instance->{$name} = $value;
    }

    /**
     * Get public properties of an object.
     */
    public static function getPublicProperties(object $instance): array
    {
        $class = new \ReflectionClass(get_class($instance));
        $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);

        $publicProperties = [];
        foreach ($properties as $property) {
            if ($property->class == $class->getName()) {
                $publicProperties[$property->getName()] = $property->getValue($instance);
            }
        }
        return $publicProperties;
    }

    /**
     * Get public methods of an object, excluding specified exceptions.
     */
    public static function getPublicMethods(object $instance, array $exceptions = []): array
    {
        $class = new \ReflectionClass(is_string($instance) ? $instance : get_class($instance));
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        $publicMethods = [];

        foreach ($methods as $method) {
            if ($method->class == $class->getName() && !in_array($method->name, $exceptions)) {
                $publicMethods[] = $method->name;
            }
        }

        return $publicMethods;
    }

    /**
     * Check if a property is public in the object instance.
     */
    public static function propertyIsPublic(object $instance, string $propertyName): bool
    {
        $property = [];
        $reflection = new \ReflectionObject($instance);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $prop) {
            $property[] = $prop->getName();
        }

        return in_array($propertyName, $property);
    }

    /**
     * Check if a method is public in the object instance.
     */
    public static function methodIsPublic(object $instance, string $methodName): bool
    {
        $methods = [];
        $reflection = new \ReflectionObject($instance);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($publicMethods as $method) {
            $methods[] = $method->getName();
        }

        return in_array($methodName, $methods);
    }

    /**
     * Reset specified properties to their initial values or default values.
     */
    public static function reset(object $instance, ...$properties): void
    {
        $propertyKeys = array_keys(self::getPublicProperties($instance));

        // Keys to reset from array
        if (count($properties) && is_array($properties[0])) {
            $properties = $properties[0];
        }

        // Reset all
        if (empty($properties)) {
            $properties = $propertyKeys;
        }

        foreach ($properties as $property) {
            $freshInstance = new $instance();
            $instance->{$property} = $freshInstance->{$property};
        }
    }

    /**
     * Allows the resetting of properties from outside the class.
     */
    public static function resetProperty(object $instance, string $name, mixed $defaultValue): void
    {
        $instance->{$name} = $defaultValue;
    }

    /**
     * Reset all properties except the specified ones to their initial values or default values.
     */
    public static function resetExcept(object $instance, ...$properties): void
    {
        if (count($properties) && is_array($properties[0])) {
            $properties = $properties[0];
        }

        $keysToReset = array_diff(array_keys(self::getPublicProperties($instance)), $properties);
        self::reset($instance, $keysToReset);
    }

    /**
     * Get an array of all public properties of the instance.
     */
    public static function all(object $instance): array
    {
        return self::getPublicProperties($instance);
    }
}
