<?php

$brandDir = dirname(__DIR__, 2).'/public/brand';

it('ships every brand logo file', function (string $file) use ($brandDir) {
    expect($brandDir.'/'.$file)->toBeFile();
})->with([
    'lnx-logo-primary.png',
    'lnx-logo-primary-320.png',
    'lnx-logo-reversed-white.png',
    'lnx-logo-reversed-white-320.png',
    'lnx-logo-mono-navy.png',
    'lnx-mark-gradient.png',
    'lnx-gradient-rule.png',
    'readme-banner.png',
]);

it('keeps the logo lockups at the 3.096:1 ratio', function (string $file) use ($brandDir) {
    [$width, $height] = getimagesize($brandDir.'/'.$file);

    expect($width / $height)->toBeGreaterThan(3.05)->toBeLessThan(3.15);
})->with([
    'lnx-logo-primary.png',
    'lnx-logo-primary-320.png',
    'lnx-logo-reversed-white.png',
    'lnx-logo-reversed-white-320.png',
    'lnx-logo-mono-navy.png',
    'lnx-mark-gradient.png',
]);

it('ships a swatch for every brand colour', function (string $swatch) use ($brandDir) {
    expect($brandDir.'/swatches/'.$swatch.'.png')->toBeFile();
})->with([
    'lnx-navy',
    'deep-navy',
    'signal-cyan',
    'circuit-violet',
    'core-magenta',
    'mist',
    'line',
    'slate',
    'body',
]);

it('references the brand assets from the README', function () {
    $readme = file_get_contents(dirname(__DIR__, 2).'/README.md');

    expect($readme)
        ->toContain('public/brand/readme-banner.png')
        ->toContain('public/brand/lnx-logo-primary-320.png')
        ->toContain('#112540')
        ->toContain('833-569-5690');
});
