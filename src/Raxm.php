<?php

namespace Axm\Raxm;

use App;
use Axm\Raxm\Component;
use Axm\Raxm\Support\FileUploadController;


class Raxm
{
    protected static string $componentName;
    private static $instances;
    protected static $ucfirstComponentName;
    public bool $hasRenderedScripts = false;
    public bool $hasRenderedStyles = false;
    private static bool $injectedAssets = false;
    private static App $app;


    /**
     * Boot the application.
     *
     * This method registers the configuration, includes the Raxm utility helpers,
     * registers the routes, and loads the Raxm assets.
     */
    public static function boot()
    {
        self::$app = app();
        self::setSingleton();
        self::registerConfig();
        self::includeHelpers();
        self::registerRoutes();
    }

    public static function setSingleton()
    {
        app('raxm', new self());
    }

    /**
     * Register configuration settings for Raxm.
     */
    public static function registerConfig()
    {
        $pathFile = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        config()->read('raxm', $pathFile);
    }

    /**
     * This method includes the Raxm utility helpers from the `raxmUtils` file.
     */
    public static function includeHelpers(): void
    {
        helpers('raxmUtils', __DIR__);
    }

    /**
     * Register the Raxm application routes.
     *
     * This method registers the following routes:
     * - POST /raxm/update/{name}: Returns the Raxm component without a layout.
     * - POST /raxm/upload-file: Handles file uploads.
     * - GET /raxm/preview-file/{filename}: Previews a file.
     * - GET /vendor/axm/raxm/js/index.js: Returns the Raxm JavaScript assets.
     * - GET /raxm/raxm_js: Returns the Raxm JavaScript source.
     */
    public static function registerRoutes()
    {
        $router = app('router');
        $assetUrl = config('raxm.asset_url');

        $router->addRoute('POST', '/raxm/update/{name}', function ($name) {
            return self::getComponentWithoutLayout($name);
        });

        $router->addRoute('POST', '/raxm/upload-file', [FileUploadController::class, 'handle']);
        $router->addRoute('GET', '/raxm/preview-file/{filename}', function ($filename) {
            return static::previewFile($filename);
        });
        $router->addRoute('GET', $assetUrl, fn() => static::returnJavaScriptAsFile());
    }

    /**
     * Outputs the RAXM script and style tags.
     */
    public static function returnJavaScriptAsFile()
    {
        $file = DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'raxm.js';
        return static::pretendResponseIsFile(dirname(__DIR__, 1) . $file);
    }

    public static function pretendResponseIsFile(string $file, string $mimeType = 'application/javascript')
    {
        $lastModified = filemtime($file);
        $headers = static::pretendedResponseIsFileHeaders($file, $mimeType, $lastModified);

        return self::$app->response->file($file, $headers)->send();
    }

    private static function pretendedResponseIsFileHeaders(string $filename, string $mimeType, string $lastModified): array
    {
        $expires = strtotime('+1 year');
        $cacheControl = 'public, max-age=31536000';

        if (static::matchesCache($lastModified)) {
            return app()->response->make('', [
                'Expires' => static::httpDate($expires),
                'Cache-Control' => $cacheControl,
            ], 304);
        }

        $headers = [
            'Content-Type' => "$mimeType; charset=utf-8",
            'Expires' => static::httpDate($expires),
            'Cache-Control' => $cacheControl,
            'Last-Modified' => static::httpDate($lastModified),
        ];

        if (pathinfo($filename, PATHINFO_EXTENSION) === 'br') {
            $headers['Content-Encoding'] = 'br';
        }

        return $headers;
    }

    /**
     * Returns a formatted HTTP date string
     */
    static function httpDate(int $timestamp): string
    {
        return sprintf('%s GMT', gmdate('D, d M Y H:i:s', $timestamp));
    }

    /**
     * Returns a Raxm component without a layout.
     *
     * This method takes a component name as a string, parses it to determine the
     * component class name, and then mounts the component without a layout.
     */
    public static function getComponentWithoutLayout(string $component): string
    {
        $names = self::parserComponent($component);
        return self::mountComponent(new $names, true);
    }

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
     * Get the current component name.
     */
    public static function componentName(): ?string
    {
        $className = class_basename(self::$componentName);
        return $className ?? null;
    }

    /**
     * Get the instance of the currently specified component.
     */
    public static function getInstanceNowComponent(): Component
    {
        return self::$instances[self::$componentName];
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
     * Run a specified component and display its HTML.
     */
    public static function runComponent(string $componentName)
    {
        $_instance = self::getInstance($componentName);
        return $_instance->run();
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
    public static function mountComponent(object $class, bool $withoutLayout = false): string
    {
        $instance = self::$app->controller->view();
        $layoutName = config('raxm.layout');
        $view = self::runOrInitializeComponent($class);
        if (!$withoutLayout) {
            $html = $instance->setView($view)
                ->layout($layoutName)
                ->resolver()
                ->assets(static::styles(), static::scripts())
                ->get();

            self::$injectedAssets = true;
        } else {
            $html = $view;
        }

        echo $html . PHP_EOL;
        unset($html, $view);
        exit;
    }

    /**
     * Runs the component or initializes it if it hasn't been run before.
     */
    public static function runOrInitializeComponent(object $component): string
    {
        $componentName = $component::class;
        $html = self::$app->request->isPost()
            ? self::runComponent($componentName)
            : self::initializeComponent($componentName);

        return $html;
    }

    public static function matchesCache(string $lastModified): bool
    {
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

        return @strtotime($ifModifiedSince) === $lastModified;
    }

    /**
     * Outputs the RAXM script and style tags.
     */
    public static function raxmScripts(array $options = [])
    {
        // Merge the provided options with default values.
        $options = array_merge(['nonce' => 'nonce-value'], $options);

        // Generate the styles and script tags.
        $stylesTag = static::styles($options);
        $scriptTag = static::scripts($options);

        // Output the tags.
        echo $stylesTag . PHP_EOL . $scriptTag . PHP_EOL;
    }

    /**
     * Generate JavaScript assets.
     * 
     */
    protected static function js(array $options = []): string
    {
        $app = self::$app;
        $assetUrl = config('raxm.asset_url');
        $appUrl = config('raxm.app_url');

        $csrfToken = "'" . $app->getCsrfToken() . "'" ?? 'null';

        // Added nonce variable to store the nonce value if it is set in the options array. 
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';

        $windowRaxmCheck = "if (window.Raxm) { delete window.Raxm }";

        $progressBar = config('raxm.navigate.show_progress_bar', true) ? '' : 'data-no-progress-bar';

        // Added randomId variable to generate a random id for the asset path url using crc32 and rand functions. 
        $randomId = crc32(rand(1000000, 99999999));

        // Added fullAssetPath variable to store the full asset path url with the random id generated in the previous step. 
        $fullAssetPath = "{$assetUrl}?id={$randomId}";

        $script = <<<HTML
            <script src="{$fullAssetPath}" {$nonce} {$progressBar} data-csrf="{$csrfToken}" data-baseUrl="{$appUrl}"></script>
        HTML;

        return $script . PHP_EOL;
    }

    /**
     * Generates the RAXM script tags.
     */
    public static function scripts(array $options = []): string
    {
        self::$app->raxm->hasRenderedScripts = true;

        $debug = config('app.debug');
        $scripts = static::js($options);

        // HTML Label.
        $html = $debug ? ['<!-- Raxm Scripts -->'] : [];
        $html[] = $scripts;

        return implode(PHP_EOL, $html);
    }

    /**
     * Generate and return Raxm styles.
     */
    public static function styles(array $options = []): string
    {
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';
        $progressBarColor = config('raxm.navigate.progress_bar_color', '#2299dd');

        $html = <<<HTML
        <!-- Raxm Styles -->
        <style {$nonce}>
            [axm\:loading], [axm\:loading\.delay], [axm\:loading\.inline-block], [axm\:loading\.inline], [axm\:loading\.block], [axm\:loading\.flex], [axm\:loading\.table], [axm\:loading\.grid], [axm\:loading\.inline-flex] {
                display: none;
            }
            [axm\:loading\.delay\.shortest], [axm\:loading\.delay\.shorter], [axm\:loading\.delay\.short], [axm\:loading\.delay\.long], [axm\:loading\.delay\.longer], [axm\:loading\.delay\.longest] {
                display:none;
            }
            [axm\:offline][axm\:offline] {
                display: none;
            }
            [axm\:dirty]:not(textarea):not(input):not(select) {
                display: none;
            }
            [x-cloak] {
                display: none;
            }
            :root {
                --raxm-progress-bar-color: {$progressBarColor};
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
     */
    protected static function minify(string $subject): string
    {
        return preg_replace('~(\v|\t|\s{2,})~m', '', $subject);
    }
}
