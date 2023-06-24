<?php

namespace Axm\LiveAxm;


class ComponentProperties
{

    public static function getPublicPropertiesDefinedBySubClass()
    {
        $publicProperties = array_filter((new \ReflectionObject($this))->getProperties(), function ($property) {
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


    public static function getProtectedOrPrivatePropertiesDefinedBySubClass()
    {
        $properties = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);
        $data = [];

        foreach ($properties as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $property->setAccessible(true);
                $data[$property->getName()] = $this->getInitializedPropertyValue($property);
            }
        }

        return $data;
    }


    public function getInitializedPropertyValue(\ReflectionProperty $property)
    {
        // Ensures typed property is initialized in PHP >=7.4, if so, return its value,
        // if not initialized, return null (as expected in earlier PHP Versions)
        if (method_exists($property, 'isInitialized') && !$property->isInitialized($this)) {
            return null;
        }

        return $property->getValue($this);
    }


    public static function hasProperty($prop)
    {
        return property_exists(
            $this,
            $prop
        );
    }


    public static function setPropertyValue($name, $value)
    {
        return $this->{$name} = $value;
    }


    public static function getPublicProperties(Object $instance)
    {
        $class = new \ReflectionClass(get_class($instance));
        $properties = $class->getProperties(\ReflectionMethod::IS_PUBLIC);
        $publicProperties = [];

        foreach ($properties as $property) {
            if ($property->class == $class->getName()) {
                $publicProperties[$property->getName()] = $property->getValue($instance);
            }
        }
        return $publicProperties;
    }


    public static function getPublicMethods($instance, array $exceptions = [])
    {
        $class   = new \ReflectionClass(is_string($instance) ? $instance : get_class($instance));
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        $publicMethods = [];

        foreach ($methods as $method) {
            if ($method->class == $class->getName() && !in_array($method->name, $exceptions)) {
                $publicMethods[] = $method->name;
            }
        }
        return $publicMethods;
    }


    public static function propertyIsPublic(Object $instance, $propertyName)
    {
        $property   = [];
        $reflection = new \ReflectionObject($instance);
        $properties = $reflection->getProperties(\ReflectionMethod::IS_PUBLIC);

        foreach ($properties as $key => $prop) {
            $property[] = $prop->getName();
        }

        return in_array($propertyName, $property) ? true : false;
    }


    public static function methodIsPublic(Object $instance, $methodName)
    {
        $methodes   = [];
        $reflection = new \ReflectionObject($instance);
        $methods    = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $key => $method) {
            $methodes[] = $method->getName();
        }

        return in_array($methodName, $methodes) ? true : false;
    }


    public static function reset(...$properties)
    {
        $propertyKeys = array_keys($this->getPublicProperties($this));

        // Keys to reset from array
        if (count($properties) && is_array($properties[0])) {
            $properties = $properties[0];
        }

        // Reset all
        if (empty($properties)) {
            $properties = $propertyKeys;
        }

        foreach ($properties as $property) {
            $freshInstance = new static($this->id);

            $this->{$property} = $freshInstance->{$property};
        }
    }


    public static function resetExcept(...$properties)
    {
        if (count($properties) && is_array($properties[0]))
            $properties = $properties[0];

        $keysToReset = array_diff(array_keys($this->getPublicProperties($this)), $properties);
        $this->reset($keysToReset);
    }

    public static function all()
    {
        return $this->getPublicProperties($this);
    }
}
