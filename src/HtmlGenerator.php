<?php

namespace Axm\Raxm;

class HtmlGenerator
{
    public static bool $hasRenderedScripts = false;
    public static bool $hasRenderedStyles  = false;
    public static bool $injectedAssets = false;


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
        $assetUrl = config('raxm.asset_url');
        $appUrl = config('raxm.app_url');

        $csrfToken = "'" . app()->getCsrfToken() . "'" ?? 'null';

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
        self::$hasRenderedScripts = true;

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