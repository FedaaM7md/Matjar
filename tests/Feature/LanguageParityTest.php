<?php

/**
 * Arabic and English must expose the exact same key set, otherwise a locale
 * silently falls back to another language. This walks every language file and
 * compares the flattened keys, including nested groups.
 */
function flattenTranslationKeys(array $array, string $prefix = ''): array
{
    $keys = [];

    foreach ($array as $key => $value) {
        $full = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        if (is_array($value)) {
            $keys = array_merge($keys, flattenTranslationKeys($value, $full));
        } else {
            $keys[] = $full;
        }
    }

    sort($keys);

    return $keys;
}

test('every language file has identical Arabic and English key sets', function () {
    $arFiles = collect(glob(lang_path('ar').'/*.php'))->map(fn ($p) => basename($p))->sort()->values();
    $enFiles = collect(glob(lang_path('en').'/*.php'))->map(fn ($p) => basename($p))->sort()->values();

    expect($arFiles->all())->toBe($enFiles->all());

    foreach ($arFiles as $file) {
        $ar = flattenTranslationKeys(require lang_path('ar/'.$file));
        $en = flattenTranslationKeys(require lang_path('en/'.$file));

        $missingInEn = array_diff($ar, $en);
        $missingInAr = array_diff($en, $ar);

        expect($missingInEn)->toBe([]);
        expect($missingInAr)->toBe([]);
    }
});

test('the brand name stands alone in both languages', function () {
    expect(__('common.app_name', [], 'ar'))->toBe('قُطن');
    expect(__('common.app_name', [], 'en'))->toBe('Qoton');

    expect(__('common.app_tagline', [], 'ar'))->toBe('ملابس يومية مصرية');
    expect(__('common.app_tagline', [], 'en'))->toBe('Everyday Wear');
});

test('page titles do not contain the owner name', function () {
    foreach (['ar', 'en'] as $locale) {
        foreach (['home.meta_title', 'shop.meta_description', 'common.meta_description'] as $key) {
            expect(__($key, [], $locale))->not->toContain('Fedaa')->and(__($key, [], $locale))->not->toContain('فداء');
        }
    }
});
