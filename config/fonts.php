<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default font slug
    |--------------------------------------------------------------------------
    |
    | Used when Font::driver() / Font::font() are called without a name.
    | Mirrored by config('tubes.defaults.font').
    |
    */

    'default' => env('FONT_DRIVER', 'classic'),

    /*
    |--------------------------------------------------------------------------
    | Built-in / app font registry
    |--------------------------------------------------------------------------
    |
    | Companions (e.g. scrapyard-io/autopen) typically Font::extend() at boot.
    | Optional config entries: [ 'slug' => ['class' => FQCN, 'enabled' => true] ]
    |
    */

    'classic' => [
        'class' => ScrapyardIO\Tubes\Fonts\ClassicFont::class,
        'enabled' => true,
    ],
];
