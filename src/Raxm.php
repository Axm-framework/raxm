<?php

namespace Axm\Raxm;

use App;
use Axm\Raxm\Support\FileUploadController;


class Raxm extends ComponentManager
{
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

    public static function matchesCache(string $lastModified): bool
    {
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

        return @strtotime($ifModifiedSince) === $lastModified;
    }

}
