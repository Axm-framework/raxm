<?php

namespace Axm\Raxm;

use Axm;
use Axm\Raxm\Component;
use Axm\Exception\AxmException;


class RaxmManager
{

    protected static string $componentName;
    private static array $instances = [];
    protected static $ucfirstComponentName;

    /**
     * 
     */
    public static function getInstance(string $componentName): Component
    {
        self::$ucfirstComponentName = ucfirst(strtolower($componentName));
        $className = '\App\Raxm\\' . self::$ucfirstComponentName;

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
        $_instance = RaxmManager::getInstance($component);

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
    public static function raxmScripts(array $options = [])
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
        $app->config()->load(APP_PATH . '/Config/Raxm.php');
        $assetUrl = $app->config()->get('asset_url');
        $fileName = $app->config()->get('fileName');
        $appUrl   = $app->config()->get('app_url');

        $jsRaxmToken = "'" . $app->getCsrfToken() . "'"  ??  'null';

        // Added nonce variable to store the nonce value if it is set in the options array. 
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';

        // Added assetWarning variable to store the warning message if app debug is set to true. 
        $assetWarning = $app->config()->get('app.debug') ? "if (window.Raxm) { console.warn('Raxm: It looks like Raxm\'s @RaxmScripts JavaScript assets have already been loaded. Make sure you aren\'t loading them twice.'); }" : '';

        // Added randomId variable to generate a random id for the asset path url using crc32 and rand functions. 
        $randomId = crc32(rand(1000000, 99999999));

        // Added fullAssetPath variable to store the full asset path url with the random id generated in the previous step. 
        $fullAssetPath = ("{$assetUrl}{$fileName}?id={$randomId}");

        return <<<HTML
            {$assetWarning}
            <script type="module" src="{$fullAssetPath}" {$nonce}></script>
            <script>
                window.raxm_app_url = '{$appUrl}';
                window.raxm_token = {$jsRaxmToken};
            </script>
        HTML;
    }


    /**
     * 
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
        </style>
        HTML;

        return static::minify($html);
    }


    /**
     * 
     */
    protected static function getComponentClass(string $componentName): string
    {
        return '\\App\\Raxm\\' . ucfirst(strtolower($componentName));
    }

    /**
     * 
     */
    protected static function minify($subject)
    {
        return preg_replace('~(\v|\t|\s{2,})~m', '', $subject);
    }
}
