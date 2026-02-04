<?php

return [
    'disk' => env('PDF_DISK', 'local'),

    'mpdf' => [
        'showImageErrors' => true,
        'debug' => false,
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
        'allow_charset_conversion' => false,
        'useKerning' => false,
        'useLigatures' => false,
        'jpeg_quality' => 78,
    ],
];
