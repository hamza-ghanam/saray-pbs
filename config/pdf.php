<?php

return [
    'mode'                     => '',
    'format'                   => 'A4',
    'default_font_size'        => '12',
    'default_font'             => 'sans-serif',
    'margin_left'              => 10,
    'margin_right'             => 10,
    'margin_top'               => 10,
    'margin_bottom'            => 10,
    'margin_header'            => 0,
    'margin_footer'            => 0,
    'orientation'              => 'P',
    'title'                    => 'Laravel mPDF',
    'subject'                  => '',
    'author'                   => '',
    'watermark'                => '',
    'show_watermark'           => false,
    'show_watermark_image'     => false,
    'watermark_font'           => 'sans-serif',
    'display_mode'             => 'fullpage',
    'watermark_text_alpha'     => 0.1,
    'watermark_image_path'     => '',
    'watermark_image_alpha'    => 0.2,
    'watermark_image_size'     => 'D',
    'watermark_image_position' => 'P',
    'custom_font_dir'          => base_path('resources/fonts/'),
    'custom_font_data'         => [
        'rubic' => [ // must be lowercase and snake_case
            'R' => 'Rubik-Regular.ttf',    // regular font
            'B' => 'Rubik-Bold.ttf',       // optional: bold font
            'I' => 'Rubik-Italic.ttf',     // optional: italic font
            'BI' => 'Rubik-BoldItalic.ttf', // optional: bold-italic font
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ],
        'notosans' => [ // must be lowercase and snake_case
            'R' => 'NotoSansArabic-Regular.ttf',    // regular font
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ],
        'dejavusans' => [ // must be lowercase and snake_case
            'R' => 'DejaVuSans.ttf',
            'B' => 'DejaVuSans-Bold.ttf',
            'I' => 'DejaVuSans-Oblique.ttf',
            'BI' => 'DejaVuSans-BoldOblique.ttf',
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ],
        'xbzar' => [ // must be lowercase and snake_case
            'R' => 'XB_Riyaz.ttf',
            'B' => 'XB_ZarBd.ttf',
            'I' => 'XB_ZarIt.ttf',
            'BI' => 'XB_ZarBdIt.ttf',
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ],
    ],
    'auto_language_detection'  => false,
    'temp_dir'                 => storage_path('app'),
    'pdfa'                     => false,
    'pdfaauto'                 => false,
    'use_active_forms'         => false,
    'debug' => [
        'mpdf'          => false,
        'fonts'         => false,
        'show_warnings' => false,
    ],
];
