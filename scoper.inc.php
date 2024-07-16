<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

// Example of collecting files to include in the scoped build but to not scope
// leveraging the isolated finder.
// $excludedFiles = array_map(
//     static fn (SplFileInfo $fileInfo) => $fileInfo->getPathName(),
//     iterator_to_array(
//         Finder::create()->files()->in(__DIR__),
//         false,
//     ),
// );
$excludedFiles = [];

return [
    // The prefix configuration. If a non-null value is used, a random prefix
    // will be generated instead.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
    'prefix' => null,

    // The base output directory for the prefixed files.
    // This will be overridden by the 'output-dir' command line option if present.
    'output-dir' => null,

    // By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // This configuration entry is completely ignored when using Box.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
    'finders' => [
        /*
        Finder::create()->files()->in('src'),
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
            ->exclude([
                'doc',
                'test',
                'test_old',
                'tests',
                'Tests',
                'vendor-bin',
            ])
            ->in('vendor'),
        Finder::create()->append([
            'composer.json',
        ]),
        */
    ],

    // List of excluded files, i.e. files for which the content will be left untouched.
    // Paths are relative to the configuration file unless if they are already absolute
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'exclude-files' => [
        // 'src/an-excluded-file.php',
        ...$excludedFiles,
    ],

    // PHP version (e.g. `'7.2'`) in which the PHP parser and printer will be configured into. This will affect what
    // level of code it will understand and how the code will be printed.
    // If none (or `null`) is configured, then the host version will be used.
    'php-version' => null,

    // When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
    // original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
    // support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
    // heart contents.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'patchers' => [
        static function (string $filePath, string $prefix, string $contents): string {
            // Change the contents here.

            return $contents;
        },
    ],

    // List of symbols to consider internal i.e. to leave untouched.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
    'exclude-namespaces' => [
        // 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
        // '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
        // '~^$~',                        // The root namespace only
        // '',                            // Any namespace
        'Op\\',
        'OpLib\\',
        'WpEloquent\\',
    ],
    'exclude-classes' => [
        // 'ReflectionClassConstant',
    ],
    'exclude-functions' => [
        'humbug_phpscoper_expose_class',
        'WP_Filesystem',
        '__',
        'add_action',
        'add_filter',
        'add_submenu_page',
        'app',
        'append_config',
        'apply_filters',
        'array_key_first',
        'array_key_last',
        'blank',
        'calculateTranslationStatus',
        'class_basename',
        'class_uses_recursive',
        'collect',
        'composerRequire901b2abf9521e00861fea82256326b68',
        'ctype_alnum',
        'ctype_alpha',
        'ctype_cntrl',
        'ctype_digit',
        'ctype_graph',
        'ctype_lower',
        'ctype_print',
        'ctype_punct',
        'ctype_space',
        'ctype_upper',
        'ctype_xdigit',
        'current_user_can',
        'data_fill',
        'data_get',
        'data_set',
        'delete_option',
        'do_action',
        'e',
        'env',
        'extractLocaleFromFilePath',
        'extractTranslationKeys',
        'fdiv',
        'filled',
        'findTransUnitMismatches',
        'findTranslationFiles',
        'flush_rewrite_rules',
        'getHtmlAttribute',
        'getMaxHistoryMonthsByAmount',
        'getOpenCollectiveSponsors',
        'get_debug_type',
        'get_footer',
        'get_header',
        'get_locale',
        'get_plugin_data',
        'get_queried_object',
        'get_resource_id',
        'get_site_url',
        'get_stylesheet_directory',
        'get_template_directory_uri',
        'grapheme_extract',
        'grapheme_stripos',
        'grapheme_stristr',
        'grapheme_strlen',
        'grapheme_strpos',
        'grapheme_strripos',
        'grapheme_strrpos',
        'grapheme_strstr',
        'grapheme_substr',
        'head',
        'hrtime',
        'isTranslationCompleted',
        'is_404',
        'is_admin',
        'is_archive',
        'is_countable',
        'is_product',
        'is_product_category',
        'is_woocommerce',
        'is_wp_error',
        'last',
        'mb_check_encoding',
        'mb_chr',
        'mb_convert_case',
        'mb_convert_encoding',
        'mb_convert_variables',
        'mb_decode_mimeheader',
        'mb_decode_numericentity',
        'mb_detect_encoding',
        'mb_detect_order',
        'mb_encode_mimeheader',
        'mb_encode_numericentity',
        'mb_encoding_aliases',
        'mb_get_info',
        'mb_http_input',
        'mb_http_output',
        'mb_internal_encoding',
        'mb_language',
        'mb_lcfirst',
        'mb_list_encodings',
        'mb_ord',
        'mb_output_handler',
        'mb_parse_str',
        'mb_scrub',
        'mb_str_pad',
        'mb_str_split',
        'mb_stripos',
        'mb_stristr',
        'mb_strlen',
        'mb_strpos',
        'mb_strrchr',
        'mb_strrichr',
        'mb_strripos',
        'mb_strrpos',
        'mb_strstr',
        'mb_strtolower',
        'mb_strtoupper',
        'mb_strwidth',
        'mb_substitute_character',
        'mb_substr',
        'mb_substr_count',
        'mb_ucfirst',
        'normalizer_is_normalized',
        'normalizer_normalize',
        'object_get',
        'optional',
        'plugins_url',
        'preg_last_error_msg',
        'preg_replace_array',
        'printTable',
        'printTitle',
        'printTranslationStatus',
        'remove_accents',
        'retry',
        'sanitize_file_name',
        'sanitize_title',
        'set_post_thumbnail',
        'str_contains',
        'str_ends_with',
        'str_starts_with',
        'tap',
        'textColorGreen',
        'textColorNormal',
        'textColorRed',
        'throw_if',
        'throw_unless',
        'trait_uses_recursive',
        'transform',
        'trigger_deprecation',
        'unzip_file',
        'update_option',
        'value',
        'wc_update_product_lookup_tables',
        'windows_os',
        'with',
        'wp_check_filetype',
        'wp_enqueue_style',
        'wp_generate_attachment_metadata',
        'wp_get_image_editor',
        'wp_get_registered_image_subsizes',
        'wp_insert_attachment',
        'wp_mkdir_p',
        'wp_set_object_terms',
        'wp_set_post_categories',
        'wp_set_post_terms',
        'wp_update_attachment_metadata',
        'wp_update_term',
        'wp_upload_dir',
        'wpml_get_hierarchy_sync_helper',
    ],
    'exclude-constants' => [
        // 'STDIN',
    ],

    // List of symbols to expose.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols
    'expose-global-constants' => true,
    'expose-global-classes' => true,
    'expose-global-functions' => true,
    'expose-namespaces' => [
        // 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
        // '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
        // '~^$~',                        // The root namespace only
        // '',                            // Any namespace
    ],
    'expose-classes' => [],
    'expose-functions' => [
        'collect',
    ],
    'expose-constants' => [],
];
