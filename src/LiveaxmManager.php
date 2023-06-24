<?php

namespace Axm\LiveAxm;

use Axm;
use Axm\LiveAxm\Component;
use Axm\Exception\AxmException;


class LiveaxmManager
{

    protected static string $componentName;
    private static $instances;
    protected static $ucfirstComponentName;

    /**
     * 
     */
    public static function getInstance(string $componentName): Component
    {
        self::$ucfirstComponentName = ucfirst(strtolower($componentName));
        $className = '\App\LiveAxm\\' . self::$ucfirstComponentName;

        if (!class_exists($className)) {
            throw new AxmException("No se encontró la clase $className.");
        }

        self::$componentName = $className;
        return self::$instances[$className] ??= new $className;
    }

    /**
     * 
     */
    public static function getInstanceNowComponent(): Component
    {
        return self::$instances[self::$componentName];
    }


    /**
     * @return mixed
     */
    public static function setInstance(string $componentName)
    {
        return self::getInstance($componentName);
    }


    /**
     * @param string $component
     * @return void
     */
    public static function initialComponent(string $component): void
    {
        $component = ucfirst($component);
        $_instance = LiveaxmManager::getInstance($component);

        if (!$_instance instanceof $component) {
            $id   = bin2hex(random_bytes(10));
            $html = $_instance->initialInstance($id);
        } else {
            $new_instance = $_instance->setInstance($component);
            $html = $new_instance->html();
        }

        echo $html;
    }


    /**
     * 
     */
    public static function liveaxmScripts(array $options = [])
    {
        $options = array_merge([
            'nonce' => 'nonce-value'
        ], $options);

        $scriptTag = static::javaScriptAssets([
            'nonce' => $options['nonce'],
        ]);

        echo $scriptTag;
    }


    /**
     * 
     */
    protected static function javaScriptAssets(array $options = [])
    {
        $app = Axm::app();
        $app->config()->load(APP_PATH . '/Config/Liveaxm.php');
        $assetUrl = $app->config()->get('asset_url');
        $fileName = $app->config()->get('fileName');

        // Added nonce variable to store the nonce value if it is set in the options array. 
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';

        // Added assetWarning variable to store the warning message if app debug is set to true. 
        $assetWarning = $app->config()->get('app.debug') ? "if (window.liveaxm) { console.warn('Liveaxm: It looks like Liveaxm\'s @liveaxmScripts JavaScript assets have already been loaded. Make sure you aren\'t loading them twice.'); }" : '';

        // Added randomId variable to generate a random id for the asset path url using crc32 and rand functions. 
        $randomId = crc32(rand(1000000, 99999999));

        // Added fullAssetPath variable to store the full asset path url with the random id generated in the previous step. 
        $fullAssetPath = baseUrl("{$assetUrl}{$fileName}?id={$randomId}");

        return <<<HTML
            {$assetWarning}
            <script type="module" src="{$fullAssetPath}" {$nonce}></script>
        HTML;
    }

    
    /**
     * 
     */
    public static function js($expression)
    {
        if (is_object($expression) || is_array($expression)) {
            $json = json_encode($expression);
            return "JSON.parse(" . htmlspecialchars($json, ENT_QUOTES, 'UTF-8') . ")";
        } elseif (is_string($expression)) {
            $escapedExpression = str_replace("\"", "\\\"", $expression);
            return "\"" . htmlspecialchars($escapedExpression, ENT_QUOTES, 'UTF-8') . "\"";
        } else {
            $json = json_encode($expression);
            return htmlspecialchars($json, ENT_QUOTES, 'UTF-8');
        }
    }


    /**
     * 
     */
    public function cssStyle($includeStyleTag = true)
    {
    }


    /**
     * 
     */
    protected static function minify(string $string): string
    {
        return preg_replace('/\s+/', '', $string);
    }
}
