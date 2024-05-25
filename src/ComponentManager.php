<?php

namespace Axm\Raxm;

class ComponentManager
{
    protected static string $componentName;
    private static array $instances;

    /**
     * Parses a component name into a fully-qualified class name.
     *
     * This method takes a component name as a string, removes the "raxm" prefix (if any),
     * appends the "Raxm" suffix, and then returns the fully-qualified class name by
     * concatenating the component name with the namespace specified in the Raxm
     * configuration.
     */
    public static function parserComponent(string $component): string
    {
        $component = str_ireplace('raxm', '', $component);
        $componentName = $component . 'Raxm';
        $nameSpace = config('raxm.class_namespace');

        return $nameSpace . ucfirst($componentName);
    }

    /**
     * Get an instance of a specified component.
     */
    public static function getInstance(string $className): Component
    {
        self::$componentName = $className;
        return self::$instances[$className] ??= new $className;
    }

    /**
     * Initialize a specified component and display its HTML.
     */
    public static function initializeComponent(string $componentName): string
    {
        $_instance = self::getInstance($componentName);
        $html = $_instance->initialInstance(bin2hex(random_bytes(10)));

        return $html;
    }

    /**
     * Get the instance of the currently specified component.
     */
    public static function getInstanceNowComponent(): Component
    {
        return self::$instances[self::$componentName];
    }

    /**
     * Run a specified component and display its HTML.
     */
    public static function runComponent(string $componentName)
    {
        $_instance = self::getInstance($componentName);
        return $_instance->run();
    }

    /**
     * Runs the component or initializes it if it hasn't been run before.
     */
    public static function runOrInitializeComponent(object $component): string
    {
        $componentName = $component::class;
        $html = app()->request->isPost()
            ? self::runComponent($componentName)
            : self::initializeComponent($componentName);

        return $html;
    }

    /**
     * Get the current component name.
     */
    public static function componentName(): ?string
    {
        $className = class_basename(self::$componentName);
        return $className ?? null;
    }

    /**
     * Returns a Raxm component without a layout.
     *
     * This method takes a component name as a string, parses it to determine the
     * component class name, and then mounts the component without a layout.
     */
    public static function getComponentWithoutLayout(string $component)
    {
        $names = self::parserComponent($component);
        return self::mountComponent(new $names, true);
    }

    /**
     * Mounts a component instance and returns the resulting HTML.
     *
     * This method takes a component class object and an optional "withoutLayout" flag,
     * and returns the resulting HTML. If the "withoutLayout" flag is false (which is
     * the default), the component will be rendered within the layout specified in
     * the Raxm configuration. If the "withoutLayout" flag is true, the component will
     * be rendered without a layout.
     */
    public static function mountComponent(object $class, bool $withoutLayout = false): ?string
    {
        $instance = app()->controller->view();
        $layoutName = config('raxm.layout');
        $view = self::runOrInitializeComponent($class);
        if (!$withoutLayout) {
            $html = $instance->setView($view)
                ->layout($layoutName)
                ->resolver()
                ->assets(HtmlGenerator::styles(), HtmlGenerator::scripts())
                ->get();

            HtmlGenerator::$injectedAssets = true;
        } else {
            $html = $view;
        }

        return $html;
    }
}
