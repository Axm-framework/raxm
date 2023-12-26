<?php

namespace Axm\Raxm;

use Axm\Raxm\Component;
use Axm\Exception\AxmException;
use Axm\Views\View;

class RaxmManager
{
    protected static string $componentName;
    private static $instances;
    protected static $ucfirstComponentName;
    public $hasRenderedScripts = false;
    public $hasRenderedStyles  = false;

    /**
     * Bootstrap the RaxmManager by registering configuration 
     * and initializing the EventBus.
     */
    public static function boot()
    {
        self::registerConfig();
        self::bootEventBus();
    }

    /**
     * Include helper functions, typically used for utility functions.
     */
    public static function includeHelpers()
    {
        helpers('raxmUtils', __DIR__);
    }

    /**
     * Register configuration settings for RaxmManager.
     * @return void
     */
    public static function registerConfig()
    {
        $pathFile = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $file = 'raxm.php';
        config()->load($file, true, $pathFile);
    }

    /**
     * Initialize the EventBus component.
     * @return void
     */
    protected static function bootEventBus()
    {
        (new EventBus)->boot();
    }

    /**
     * Get an instance of a specified component.
     * 
     * @param string $componentName The name of the component to retrieve.
     * @return Component An instance of the specified component.
     * @throws AxmException if the specified component class does not exist.
     */
    public static function getInstance(string $className): Component
    {
        self::$componentName = $className;
        return self::$instances[$className] ??= new $className;
    }

    /**
     * Get the current component name.
     * @return string|null The current component name.
     */
    public static function componentName()
    {
        $className = class_basename(self::$componentName);
        return $className ?? null;
    }

    /**
     * Get the instance of the currently specified component.
     * @return Component An instance of the currently specified component.
     */
    public static function getInstanceNowComponent(): Component
    {
        return self::$instances[self::$componentName];
    }

    /**
     * Initialize a specified component and display its HTML.
     * 
     * @param string $componentName The name of the component to initialize.
     * @throws AxmException if the specified component class does not exist.
     */
    public static function initializeComponent(string $componentName)
    {
        $_instance = self::getInstance($componentName);

        $id = bin2hex(random_bytes(10));
        $html = $_instance->initialInstance($id);
        return $html;
    }

    /**
     * Run a specified component and display its HTML.
     * 
     * @param string $componentName The name of the component to run.
     * @throws AxmException if the specified component class does not exist.
     */
    public static function runComponent(string $componentName)
    {
        $_instance = self::getInstance($componentName);
        return $_instance->run();
    }

    /**
     * mountComponent
     *
     * @param  mixed $class
     * @return void
     */
    public static function mountComponent(Object $class)
    {
        self::boot();
        $view_instance = View::make();
        $view = $view_instance::captureFile(
            $view_instance::$layoutPath . DIRECTORY_SEPARATOR . $view_instance::$nameLayoutByDefault . '.php',
            ['_content' => self::compileComponent($class)]
        );

        $html = $view_instance::injectAssets($view, static::styles(), static::scripts());
        echo $html . PHP_EOL;
    }

    /**
     * compileComponent
     * 
     * @param Object $component
     * @return string
     */
    public static function compileComponent(Object $component)
    {
        $componentName = $component::class;
        $html = app()->request->isPost()
            ? self::runComponent($componentName)
            : self::initializeComponent($componentName);

        return $html;
    }

    /**
     * Echo Raxm styles and JavaScript assets.
     * 
     * @param array $options Additional options for raxmScripts.
     * @return void
     */
    public static function raxmScripts(array $options = [])
    {
        // Merge the provided options with default values.
        $options = array_merge([
            'nonce' => 'nonce-value'
        ], $options);

        // Generate the styles and script tags.
        $stylesTag = static::styles($options);
        $scriptTag = static::scripts($options);

        // Output the tags.
        echo $stylesTag . PHP_EOL . $scriptTag . PHP_EOL;
    }

    /**
     * Generate JavaScript assets.
     * 
     * @param array $options Additional options for JavaScript assets.
     * @return string The generated JavaScript assets as HTML script tags.
     */
    protected static function js(array $options = [])
    {
        $app = app();
        $assetUrl = $app->config('raxm.asset_url');
        $fileName = $app->config('raxm.fileName');
        $appUrl   = $app->config('raxm.app_url');

        $csrfToken = "'" . $app->getCsrfToken() . "'"  ??  'null';

        // Added nonce variable to store the nonce value if it is set in the options array. 
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';

        $windowRaxmCheck = "if (window.Raxm) { delete window.Raxm }";
        // Added randomId variable to generate a random id for the asset path url using crc32 and rand functions. 
        $randomId = crc32(rand(1000000, 99999999));

        $progressBar = $app->config('raxm.navigate.show_progress_bar', true) ? '' : 'data-no-progress-bar';

        // Added fullAssetPath variable to store the full asset path url with the random id generated in the previous step. 
        $fullAssetPath = ("{$assetUrl}{$fileName}?id={$randomId}");

        $script = <<<HTML
         <!-- <script>{$windowRaxmCheck}</script> -->
            <script src="{$fullAssetPath}" type="module" {$nonce} {$progressBar} data-csrf="{$csrfToken}" data-baseUrl="{$appUrl}"></script>
        HTML;

        return $script . PHP_EOL;
    }

    /**
     * scripts
     *
     * @param  mixed $options
     * @return void
     */
    public static function scripts($options = [])
    {
        app(static::class)->hasRenderedScripts = true;

        $debug = config('app.debug');
        $scripts = static::js($options);

        // HTML Label.
        $html = $debug ? ['<!-- Raxm Scripts -->'] : [];
        $html[] = $scripts;

        return implode(PHP_EOL, $html);
    }

    /**
     * Generate and return Raxm styles.
     * 
     * @param array $options Additional options for Raxm styles.
     * @return string The generated Raxm styles as HTML style tags.
     */
    public static function styles($options = [])
    {
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';
        $html = <<<HTML
        <!-- Raxm Styles -->
        <style {$nonce}>
            [axm\:loading], [axm\:loading\.delay], [axm\:loading\.inline-block], [axm\:loading\.inline], [axm\:loading\.block], [axm\:loading\.flex], [axm\:loading\.table], [axm\:loading\.grid], [axm\:loading\.inline-flex] {
                display: none;
            }
            [axm\:loading\.delay\.shortest], [axm\:loading\.delay\.shorter], [axm\:loading\.delay\.short], [axm\:loading\.delay\.long], [axm\:loading\.delay\.longer], [axm\:loading\.delay\.longest] {
                display:none;
            }
            [axm\:offline] {
                display: none;
            }
            [axm\:dirty]:not(textarea):not(input):not(select) {
                display: none;
            }
            [x-cloak] {
                display: none;
            }
            input:-webkit-autofill, select:-webkit-autofill, textarea:-webkit-autofill {
                animation-duration: 50000s;
                animation-name: raxmautofill;
            }
            @keyframes raxmautofill { from {} }
        </style>
        <!-- END Raxm Styles -->
        HTML;

        return static::minify($html);
    }

    /**
     * Minify the given HTML content by removing unnecessary whitespace.
     * 
     * @param string $subject The HTML content to be minified.
     * @return string The minified HTML content.
     */
    protected static function minify($subject)
    {
        return preg_replace('~(\v|\t|\s{2,})~m', '', $subject);
    }
}
