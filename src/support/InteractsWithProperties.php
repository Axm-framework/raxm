<?php

namespace Axm\Raxm\Support;

use ReflectionObject;
use ReflectionProperty;
use Illuminate\Database\Eloquent\Model;


trait InteractsWithProperties
{
    public function handleHydrateProperty($property, $value)
    {
        $newValue = $value;

        if (method_exists($this, 'hydrateProperty')) {
            $newValue = $this->hydrateProperty($property, $newValue);
        }

        foreach (array_diff(class_uses_recursive($this), class_uses(self::class)) as $trait) {
            $method = 'hydratePropertyFrom' . class_basename($trait);

            if (method_exists($this, $method)) {
                $newValue = $this->{$method}($property, $newValue);
            }
        }

        return $newValue;
    }

    public function handleDehydrateProperty($property, $value)
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

    public function getPublicPropertiesDefinedBySubClass()
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

    public function getProtectedOrPrivatePropertiesDefinedBySubClass()
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

    public function getInitializedPropertyValue(ReflectionProperty $property)
    {
        if (!$property->isInitialized($this)) {
            return null;
        }

        $property->setAccessible(true);
        return $property->getValue($this);
    }

    public function hasProperty($prop)
    {
        return property_exists($this, $this->beforeFirstDot($prop));
    }

    public function getPropertyValue($name)
    {
        $value = $this->{$this->beforeFirstDot($name)};

        if ($this->containsDots($name)) {
            return data_get($value, $this->afterFirstDot($name));
        }

        return $value;
    }

    public function setProtectedPropertyValue($name, $value)
    {
        return $this->{$name} = $value;
    }

    public function containsDots(string $subject): bool
    {
        return strpos($subject, '.') !== false;
    }

    public function beforeFirstDot($subject)
    {
        return explode('.', $subject)[0];
    }

    public function afterFirstDot($subject)
    {
        return substr($subject, strpos($subject, '.') + 1);
    }

    public function propertyIsPublicAndNotDefinedOnBaseClass($propertyName)
    {
        $publicProperties = (new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC);
        $propertyNames = array_map(function ($property) {
            return $property->name;
        }, $publicProperties);

        return in_array($propertyName, $propertyNames);
    }

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

    protected function resetExcept(...$properties)
    {
        if (count($properties) && is_array($properties[0])) {
            $properties = $properties[0];
        }

        $keysToReset = array_diff(array_keys($this->getPublicPropertiesDefinedBySubClass()), $properties);
        $this->reset($keysToReset);
    }

    public function only($properties)
    {
        $results = [];

        foreach ($properties as $property) {
            $results[$property] = $this->hasProperty($property) ? $this->getPropertyValue($property) : null;
        }

        return $results;
    }

    public function except($properties)
    {
        return array_diff_key($this->all(), array_flip($properties));
    }

    public function all()
    {
        return $this->getPublicPropertiesDefinedBySubClass();
    }
}
