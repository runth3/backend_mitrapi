<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'API Documentation', // Judul yang lebih generik
            ],

            'routes' => [
                /*
                 * Route for accessing api documentation interface
                 */
                'api' => 'api/documentation', // Tetap menggunakan ini
            ],
            'paths' => [
                /*
                 * Edit to include full URL in ui for assets
                 */
                'use_absolute_path' => true, // Tetap aktif

                /*
                * Edit to set path where swagger ui assets should be stored
                */
                'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),

                /*
                 * File name of the generated json documentation file
                 */
                'docs_json' => 'api-docs.json',

                /*
                 * File name of the generated YAML documentation file
                 */
                'docs_yaml' => 'api-docs.yaml',

                /*
                 * Set this to `json` or `yaml` to determine which documentation file to use in UI
                 */
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),

                /*
                 * Absolute paths to directory containing the swagger annotations are stored.
                 */
                'annotations' => [
                    base_path('app/Http/Swagger'),
                    base_path('app/Http/Controllers'), // Hanya memindai controller
                    base_path('app/Models'), // Memindai model untuk skema respons 
                    base_path('app/Http/Resources'), // Tambahkan direktori ini
                ],
            ],
        ],
    ],
    'defaults' => [
        'routes' => [
            /*
             * Route for accessing parsed swagger annotations.
             */
            'docs' => 'docs', // Biarkan default

            /*
             * Route for Oauth2 authentication callback.
             */
            'oauth2_callback' => 'api/oauth2-callback', // Biarkan default

            /*
             * Middleware allows to prevent unexpected access to API documentation
             */
            'middleware' => [
                'api' => [
                    // Anda bisa menambahkan middleware di sini jika perlu proteksi,
                    // contoh: 'auth:sanctum' jika hanya user terotentikasi yang boleh melihat
                ],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],

            /*
             * Route Group options
             */
            'group_options' => [],
        ],

        'paths' => [
            /*
             * Absolute path to location where parsed annotations will be stored
             */
            'docs' => storage_path('api-docs'), // Biarkan default

            /*
             * Absolute path to directory where to export views
             */
            'views' => base_path('resources/views/vendor/l5-swagger'), // Biarkan default

            /*
             * Edit to set the api's base path
             */
            'base' => env('L5_SWAGGER_BASE_PATH', null), // Biarkan default

            /*
             * Absolute path to directories that should be excluded from scanning
             * @deprecated Please use `scanOptions.exclude`
             * `scanOptions.exclude` overwrites this
             */
            'excludes' => [], // Biarkan default
        ],

        'scanOptions' => [
            'default_processors_configuration' => [],
            'analyser' => null,
            'analysis' => null,
            'processors' => [],
            'pattern' => null,
            'exclude' => [
                base_path('vendor'), // Secara default, kecualikan vendor
                base_path('bootstrap'), // Kecualikan bootstrap
                base_path('config'), // Kecualikan config
                base_path('database'), // Kecualikan database
                base_path('routes'), // Kecualikan routes
                base_path('storage'), // Kecualikan storage
                base_path('tests'), // Kecualikan tests
            ],
            'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_SPEC_VERSION', \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION),
        ],

        /*
         * API security definitions. Will be generated into documentation file.
        */
        'securityDefinitions' => [
            'securitySchemes' => [
                'bearerAuth' => [ // Nama skema keamanan, bisa disesuaikan
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                    'description' => 'Enter token in format (Bearer <token>)',
                ],
                // Anda bisa menambahkan skema keamanan lain jika diperlukan (misalnya, OAuth2)
            ],
            'security' => [
                [
                    'bearerAuth' => [], // Terapkan bearerAuth secara global jika sebagian besar endpoint memerlukan otentikasi
                ],
            ],
        ],

        /*
         * Set this to `true` in development mode so that docs would be regenerated on each request
         * Set this to `false` to disable swagger generation on production
         */
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

        /*
         * Set this to `true` to generate a copy of documentation in yaml format
         */
        'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),

        /*
         * Edit to trust the proxy's ip address - needed for AWS Load Balancer
         * string[]
         */
        'proxy' => false,

        /*
         * Configs plugin allows to fetch external configs instead of passing them to SwaggerUIBundle.
         * See more at: https://github.com/swagger-api/swagger-ui#configs-plugin
         */
        'additional_config_url' => null,

        /*
         * Apply a sort to the operation list of each API. It can be 'alpha' (sort by paths alphanumerically),
         * 'method' (sort by HTTP method).
         * Default is the order returned by the server unchanged.
         */
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),

        /*
         * Pass the validatorUrl parameter to SwaggerUi init on the JS side.
         * A null value here disables validation.
         */
        'validator_url' => null,

        /*
         * Swagger UI configuration parameters
         */
        'ui' => [
            'display' => [
                'dark_mode' => env('L5_SWAGGER_UI_DARK_MODE', false),
                /*
                 * Controls the default expansion setting for the operations and tags. It can be :
                 * 'list' (expands only the tags),
                 * 'full' (expands the tags and operations),
                 * 'none' (expands nothing).
                 */
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'list'), // Lebih ringkas saat pertama kali dibuka

                /**
                 * If set, enables filtering. The top bar will show an edit box that
                 * you can use to filter the tagged operations that are shown. Can be
                 * Boolean to enable or disable, or a string, in which case filtering
                 * will be enabled using that string as the filter expression. Filtering
                 * is case-sensitive matching the filter expression anywhere inside
                 * the tag.
                 */
                'filter' => env('L5_SWAGGER_UI_FILTERS', true), // Aktifkan filter
            ],

            'authorization' => [
                /*
                 * If set to true, it persists authorization data, and it would not be lost on browser close/refresh
                 */
                'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', false),

                'oauth2' => [
                    /*
                     * If set to true, adds PKCE to AuthorizationCodeGrant flow
                     */
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
        ],
        /*
         * Constants which can be used in annotations
         */
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', env('APP_URL', 'http://localhost')), // Gunakan APP_URL jika tersedia
        ],
    ],
];