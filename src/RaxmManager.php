<?php

namespace Axm\Raxm;

use Axm;
use Axm\Raxm\Component;
use Axm\Exception\AxmException;


class RaxmManager
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
    public static function RaxmScripts(array $options = [])
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
        $config = $app->config();
        $config->load(APP_PATH . '/Config/Raxm.php');
        $assetUrl = $config->get('asset_url');
        $fileName = $config->get('fileName');

        $defaultOptions = [
            'nonce' => null,
        ];

        $options = array_merge($defaultOptions, $options);

        $assetWarning = '';
        if ($config->get('app.debug')) {
            $assetWarning = "if (window.Raxm) { console.warn('Raxm: It looks like Raxm\'s @RaxmScripts JavaScript assets have already been loaded. Make sure you aren\'t loading them twice.'); }";
        }

        $randomId = sha1(uniqid());
        $fullAssetPath = self::generateAssetUrl($assetUrl, $fileName, $randomId);

        $nonceAttribute = $options['nonce'] ? "nonce=\"{$options['nonce']}\"" : '';

        return <<<HTML
            {$assetWarning}
            <script type="module" src="{$fullAssetPath}" {$nonceAttribute}></script>
        HTML;
    }


    protected static function generateAssetUrl($assetUrl, $fileName, $randomId)
    {
        // Concatenar la URL base del asset con el nombre del archivo y el ID aleatorio
        $fullAssetPath = $assetUrl . $fileName . '?id=' . $randomId;

        return $fullAssetPath;
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
