<?php

return [

    'raxm' => [
        /*
        |--------------------------------------------------------------------------
        | Class Namespace
        |--------------------------------------------------------------------------
        |
        | This value sets the root namespace for raxm component classes in
        | your application. This value affects component auto-discovery and
        | any raxm file helper commands, like `artisan make:raxm`.
        |
        | After changing this item, run: `php artisan raxm:discover`.
        |
        */
        'class_namespace' => 'App\\Raxm',

        /*
        |--------------------------------------------------------------------------
        | View Path
        |--------------------------------------------------------------------------
        |
        | This value sets the path for raxm component views. This affects
        | file manipulation helper commands like `artisan make:raxm`.
        |
        */
        'view_path' => APP_PATH . '/Views/raxm/',

        /*
        |--------------------------------------------------------------------------
        | Component Path
        |--------------------------------------------------------------------------
        |
        | This value sets the path for raxm component views. This affects
        | file manipulation helper commands like `artisan make:raxm`.
        |
        */
        'component_path' => APP_PATH . '/Raxm/',

        /*
        |--------------------------------------------------------------------------
        | Layout
        |--------------------------------------------------------------------------
        | The default layout view that will be used when rendering a component via
        | Route::get('/some-endpoint', SomeComponent::class);. In this case the
        | the view returned by SomeComponent will be wrapped in "layouts.app"
        |
        */
        'layout' => 'layouts.app',

        /*
        |--------------------------------------------------------------------------
        | raxm Assets URL
        |--------------------------------------------------------------------------
        |
        | This value sets the path to raxm JavaScript assets, for cases where
        | your app's domain root is not the correct path. By default, raxm
        | will load its JavaScript assets from the app's "relative root".
        |
        | Examples: "/assets", "myurl.com/app".
        |
        */
        'asset_url' => generateUrl('vendor/axm/raxm/js') . '/',

        /*
        |--------------------------------------------------------------------------
        | raxm File Name
        |--------------------------------------------------------------------------
        |
        | This value sets the filename to raxm JavaScript assets, for cases where
        | your app's domain root is not the correct path. By default, raxm
        | will load its JavaScript assets from the app's "file name".
        |
        | Examples: "cdn.js".
        |
        */
        'fileName' => 'index.js',


        /*
        |--------------------------------------------------------------------------
        | raxm App URL
        |--------------------------------------------------------------------------
        |
        | This value should be used if raxm assets are served from CDN.
        | raxm will communicate with an app through this url.
        |
        | Examples: "https://my-app.com", "myurl.com/app".
        |
        */
        'app_url' => rtrim(generateUrl(), '/'),

        /*
        |--------------------------------------------------------------------------
        | raxm Endpoint Middleware Group
        |--------------------------------------------------------------------------
        |
        | This value sets the middleware group that will be applied to the main
        | raxm "message" endpoint (the endpoint that gets hit everytime
        | a raxm component updates). It is set to "web" by default.
        |
        */
        'middleware_group' => 'web',

        /*
        |--------------------------------------------------------------------------
        | raxm Temporary File Uploads Endpoint Configuration
        |--------------------------------------------------------------------------
        |
        | raxm handles file uploads by storing uploads in a temporary directory
        | before the file is validated and stored permanently. All file uploads
        | are directed to a global endpoint for temporary storage. The config
        | items below are used for customizing the way the endpoint works.
        |
        */
        'temporary_file_upload' => [
            'disk' => null,        // Example: 'local', 's3'              | Default: 'default'
            'rules' => null,       // Example: ['file', 'mimes:png,jpg']  | Default: ['required', 'file', 'max:12288'] (12MB)
            'directory' => null,   // Example: 'tmp'                      | Default: 'Raxm-tmp'
            'middleware' => null,  // Example: 'throttle:5,1'             | Default: 'throttle:60,1'
            'preview_mimes' => [   // Supported file types for temporary pre-signed file URLs...
                'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
                'mov', 'avi', 'wmv', 'mp3', 'm4a',
                'jpg', 'jpeg', 'mpga', 'webp', 'wma',
            ],
            'max_upload_time' => 5, // Max duration (in minutes) before an upload is invalidated...
        ],

        /*
        |--------------------------------------------------------------------------
        | Back Button Cache
        |--------------------------------------------------------------------------
        |
        | This value determines whether the back button cache will be used on pages
        | that contain raxm. By disabling back button cache, it ensures that
        | the back button shows the correct state of components, instead of
        | potentially stale, cached data.
        |
        | Setting it to "false" (default) will disable back button cache.
        |
        */
        'back_button_cache' => false,

        /*
        |--------------------------------------------------------------------------
        | Render On Redirect
        |--------------------------------------------------------------------------
        |
        | This value determines whether raxm will render before it's redirected
        | or not. Setting it to "false" (default) will mean the render method is
        | skipped when redirecting. And "true" will mean the render method is
        | run before redirecting. Browsers bfcache can store a potentially
        | stale view if render is skipped on redirect.
        |
        */
        'render_on_redirect' => false,

        /*
        |---------------------------------------------------------------------------
        | Navigate (SPA mode)
        |---------------------------------------------------------------------------
        |
        | By adding `axm:navigate` to links in your Raxm application, Raxm
        | will prevent the default link handling and instead request those pages
        | via AJAX, creating an SPA-like effect. Configure this behavior here.
        |
        */
        'navigate' => [
            'show_progress_bar' => true,
        ],
    ]
];
