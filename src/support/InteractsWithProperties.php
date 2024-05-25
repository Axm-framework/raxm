<?php

namespace Axm\Raxm\Support;

use ReflectionObject;
use ReflectionProperty;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait InteractsWithProperties
 *
 * This trait provides methods for interacting with object properties, including hydration, public property retrieval,
 * property checks, and resetting properties.
 * @package Axm\Raxm\Support
 */
trait InteractsWithProperties
{
    /**
     * Handles the hydration of a specific property.
     */
    public function handleHydrateProperty(string $property, $value): mixed
    {
        $newValue = $value;

        if (method_exists($this, 'hydrateProperty')) {
            $newValue = $this->hydrateProperty(new $property, $newValue);
        }

        foreach (array_diff(class_uses_recursive($this), class_uses(self::class)) as $trait) {
            $method = 'hydratePropertyFrom' . class_basename($trait);

            if (method_exists($this, $method)) {
                $newValue = $this->{$method}($property, $newValue);
            }
        }

        return $newValue;
    }

    /**
     * Handles the dehydration of a specific property.
     */
    public function handleDehydrateProperty(string $property, $value): mixed
    {
        $newValue = $value;

        if (method_exists($this, 'dehydrateProperty')) {
            $newValue = $this->dehydrateProperty($property, $newValue);
        }

        foreach (array_diff(class_uses_recursive($this), class_uses(self::class)) as $trait) {
            $method = 'dehydratePropertyFrom' . class_basename($trait);

            if (method_exists($this, $method)) {
                $newValue = $this->{$method}($property, $newValue);
            }
        }

        return $newValue;
    }

    /**
     * Retrieves public properties defined by the subclass (excluding the base class).
     */
    public function getPublicPropertiesDefinedBySubClass(): array
    {
        $reflection = new ReflectionObject($this);
        $publicProperties = array_filter($reflection->getProperties(), function ($property) {
            return $property->isPublic() && !$property->isStatic();
        });

        $data = [];
        foreach ($publicProperties as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $data[$property->getName()] = $this->getInitializedPropertyValue($property);
            }
        }

        return $data;
    }

    /**
     * Retrieves protected or private properties defined by the subclass.
     */
    public function getProtectedOrPrivatePropertiesDefinedBySubClass(): array
    {
        $reflection = new ReflectionObject($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);

        $data = [];
        foreach ($properties as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $property->setAccessible(true);
                $data[$property->getName()] = $this->getInitializedPropertyValue($property);
            }
        }

        return $data;
    }

    /**
     * Gets the initialized value of a property using reflection.
     */
    public function getInitializedPropertyValue(ReflectionProperty $property): mixed
    {
        if (!$property->isInitialized($this)) {
            return null;
        }

        $property->setAccessible(true);
        return $property->getValue($this);
    }

    /**
     * Checks if a property exists on the object.
     */
    public function hasProperty(string $property): bool
    {
        return property_exists($this, $this->beforeFirstDot($property));
    }

    /**
     * Get the value of a property, supporting dot notation.
     */
    public function getPropertyValue(string $name): mixed
    {
        $value = $this->{$this->beforeFirstDot($name)};

        if ($this->containsDots($name)) {
            return data_get($value, $this->afterFirstDot($name));
        }

        return $value;
    }

    /**
     * Set the value of a protected property.
     */
    public function setProtectedPropertyValue(string $name, $value): mixed
    {
        return $this->{$name} = $value;
    }

    /**
     * Check if the string contains dots.
     */
    public function containsDots(string $subject): bool
    {
        return strpos($subject, '.') !== false;
    }

    /**
     * Get the substring before the first dot in a string.
     */
    public function beforeFirstDot(string $subject)
    {
        return explode('.', $subject)[0];
    }

    /**
     * Get the substring after the first dot in a string.
     */
    public function afterFirstDot(string $subject): string
    {
        return substr($subject, strpos($subject, '.') + 1);
    }

    /**
     * Check if a property is public and not defined in the base class.
     */
    public function propertyIsPublicAndNotDefinedOnBaseClass(string $propertyName): bool
    {
        $publicProperties = (new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC);
        $propertyNames = array_map(function ($property) {
            return $property->name;
        }, $publicProperties);

        return in_array($propertyName, $propertyNames);
    }

    /**
     * Fill the object's public properties with values.
     */
    public function fill($values)
    {
        $publicProperties = array_keys($this->getPublicPropertiesDefinedBySubClass());

        if ($values instanceof Model) {
            $values = $values->toArray();
        }

        foreach ($values as $key => $value) {
            if (in_array($this->beforeFirstDot($key), $publicProperties)) {
                data_set($this, $key, $value);
            }
        }
    }

    /**
     * Reset specified or all public properties to their default values.
     */
    public function reset(...$properties)
    {
        $propertyKeys = array_keys($this->getPublicPropertiesDefinedBySubClass());

        if (count($properties) && is_array($properties[0])) {
            $properties = $properties[0];
        }

        if (empty($properties)) {
            $properties = $propertyKeys;
        }

        foreach ($properties as $property) {
            $freshInstance = new static();

            $this->{$property} = $freshInstance->{$property};
        }
    }

    /**
     * Reset all public properties except the specified ones.
     */
    protected function resetExcept(...$properties)
    {
        if (count($properties) && is_array($properties[0])) {
            $properties = $properties[0];
        }

        $keysToReset = array_diff(array_keys($this->getPublicPropertiesDefinedBySubClass()), $properties);
        $this->reset($keysToReset);
    }

    /**
     * Get only the specified properties from the object.
     */
    public function only(array $properties = []): array
    {
        $results = [];

        foreach ($properties as $property) {
            $results[$property] = $this->hasProperty($property) ? $this->getPropertyValue($property) : null;
        }

        return $results;
    }

    /**
     * Exclude the specified properties from all properties of the object.
     */
    public function except(array $properties): array
    {
        return array_diff_key($this->all(), array_flip($properties));
    }

    /**
     * Get all public properties defined by the subclass.
     */
    public function all(): array
    {
        return $this->getPublicPropertiesDefinedBySubClass();
    }
}
