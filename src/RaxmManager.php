<?php

namespace Axm\Raxm;

use Axm\Raxm\Component;
use Axm\Exception\AxmException;


class RaxmManager
{
    protected static string $componentName;
    private static $instances;
    protected static $ucfirstComponentName;
    public $hasRenderedScripts = false;
    public $hasRenderedStyles  = false;


    public function register()
    {
        $this->registerRaxmSingleton();
    }

    public function registerRaxmSingleton()
    {
        app('raxm', fn () => new RaxmManager);
    }


    /**
     * Bootstrap the RaxmManager by registering configuration and initializing the EventBus.
     */
    public function boot()
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
     * 
     * @return void
     */
    public static function registerConfig()
    {
        $pathFile = dirname(__FILE__, 2);
        app()->config->load($pathFile . '/config/raxm.php');
    }

    /**
     * Initialize the EventBus component.
     * 
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
    public static function getInstance(string $componentName): Component
    {
        self::$ucfirstComponentName = ucfirst(strtolower($componentName));
        $className = '\App\Raxm\\' . self::$ucfirstComponentName;

        if (!class_exists($className)) {
            throw new AxmException("Class $className not found.");
        }

        self::$componentName = $className;
        return self::$instances[$className] ??= new $className;
    }

    /**
     * Get the current component name.
     * 
     * @return string|null The current component name.
     */
    public static function componentName()
    {
        return self::$ucfirstComponentName ?? null;
    }

    /**
     * Get the instance of the currently specified component.
     * 
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
        $component = ucfirst($componentName);
        $_instance = self::getInstance($component);

        if (!$_instance instanceof Component) {
            throw new AxmException("Class $component not found.");
        }

        $id = bin2hex(random_bytes(10));
        $html = $_instance->initialInstance($id);
        echo $html . PHP_EOL;
    }

    /**
     * Inject Raxm styles and JavaScript assets into HTML content.
     * 
     * @param string $html The HTML content to inject assets into.
     * @return string The modified HTML content with injected assets.
     */
    public static function injectAssets($html)
    {
        $raxmStyles  = static::styles();
        $raxmScripts = static::scripts();

        $html = strval($html);

        if (preg_match('/<\s*\/\s*head\s*>/i', $html) && preg_match('/<\s*\/\s*body\s*>/i', $html)) {
            $html = preg_replace('/(<\s*\/\s*head\s*>)/i', $raxmStyles  . '$1', $html);
            $html = preg_replace('/(<\s*\/\s*body\s*>)/i', $raxmScripts . '$1', $html);

            return $html;
        }

        $html = preg_replace('/(<\s*html(?:\s[^>])*>)/i', '$1' . $raxmStyles, $html);
        $html = preg_replace('/(<\s*\/\s*html\s*>)/i', $raxmScripts . '$1', $html);

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
        $assetUrl = $app->config()->get('raxm.asset_url');
        $fileName = $app->config()->get('raxm.fileName');
        $appUrl   = $app->config()->get('raxm.app_url');

        $csrfToken = "'" . $app->getCsrfToken() . "'"  ??  'null';

        // Added nonce variable to store the nonce value if it is set in the options array. 
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';

        $windowRaxmCheck = "if (window.Raxm) { delete window.Raxm }";
        // Added randomId variable to generate a random id for the asset path url using crc32 and rand functions. 
        $randomId = crc32(rand(1000000, 99999999));

        $progressBar = $app->config()->get('raxm.navigate.show_progress_bar', true) ? '' : 'data-no-progress-bar';

        // Added fullAssetPath variable to store the full asset path url with the random id generated in the previous step. 
        $fullAssetPath = ("{$assetUrl}{$fileName}?id={$randomId}");


        $script = <<<HTML
         <!-- <script>{$windowRaxmCheck}</script> -->
            <script src="{$fullAssetPath}" type="module" {$nonce} {$progressBar} data-csrf="{$csrfToken}" data-baseUrl="{$appUrl}"></script>
        HTML;

        return $script . "\n";
    }

    public static function scripts($options = [])
    {
        app(static::class)->hasRenderedScripts = true;

        $debug = config('app.debug');

        $scripts = static::js($options);

        // HTML Label.
        $html = $debug ? ['<!-- Raxm Scripts -->'] : [];

        $html[] = $scripts;

        return implode("\n", $html);
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
