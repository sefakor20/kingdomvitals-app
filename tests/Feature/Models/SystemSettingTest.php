<?php

declare(strict_types=1);

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Clear any cached settings
    SystemSetting::clearCache();
});

test('can store and retrieve string values', function (): void {
    SystemSetting::set('test_string', 'Hello World', 'test');

    $value = SystemSetting::get('test_string');

    expect($value)->toBe('Hello World');
});

test('can store and retrieve array values as JSON', function (): void {
    $array = [
        'favicon' => 'logos/favicon.png',
        'small' => 'logos/small.png',
        'medium' => 'logos/medium.png',
    ];

    SystemSetting::set('test_array', $array, 'test');

    $value = SystemSetting::get('test_array');

    expect($value)->toBeArray()
        ->and($value)->toHaveKey('favicon')
        ->and($value)->toHaveKey('small')
        ->and($value)->toHaveKey('medium')
        ->and($value['favicon'])->toBe('logos/favicon.png');
});

test('can store and retrieve boolean true', function (): void {
    SystemSetting::set('test_bool_true', true, 'test');

    $value = SystemSetting::get('test_bool_true');

    expect($value)->toBeTrue();
});

test('can store and retrieve boolean false', function (): void {
    SystemSetting::set('test_bool_false', false, 'test');

    $value = SystemSetting::get('test_bool_false');

    expect($value)->toBeFalse();
});

test('can store encrypted values', function (): void {
    $secret = 'my-secret-api-key';

    SystemSetting::set('test_encrypted', $secret, 'test', true);

    // Verify it's stored encrypted in the database
    $setting = SystemSetting::where('key', 'test_encrypted')->first();
    expect($setting->is_encrypted)->toBeTrue()
        ->and($setting->value)->not->toBe($secret);

    // Verify we can retrieve the decrypted value
    $value = SystemSetting::get('test_encrypted');
    expect($value)->toBe($secret);
});

test('returns default when key not found', function (): void {
    $value = SystemSetting::get('non_existent_key', 'default_value');

    expect($value)->toBe('default_value');
});

test('returns null when key not found and no default', function (): void {
    $value = SystemSetting::get('non_existent_key');

    expect($value)->toBeNull();
});

test('can check if setting exists', function (): void {
    SystemSetting::set('existing_key', 'value', 'test');

    expect(SystemSetting::has('existing_key'))->toBeTrue()
        ->and(SystemSetting::has('non_existing_key'))->toBeFalse();
});

test('can remove a setting', function (): void {
    SystemSetting::set('key_to_remove', 'value', 'test');
    expect(SystemSetting::has('key_to_remove'))->toBeTrue();

    SystemSetting::remove('key_to_remove');

    expect(SystemSetting::has('key_to_remove'))->toBeFalse()
        ->and(SystemSetting::get('key_to_remove'))->toBeNull();
});

test('can get all settings for a group', function (): void {
    SystemSetting::set('group_key_1', 'value1', 'my_group');
    SystemSetting::set('group_key_2', 'value2', 'my_group');
    SystemSetting::set('other_key', 'other', 'other_group');

    $groupSettings = SystemSetting::getGroup('my_group');

    expect($groupSettings)->toBeArray()
        ->and($groupSettings)->toHaveCount(2)
        ->and($groupSettings)->toHaveKey('group_key_1')
        ->and($groupSettings)->toHaveKey('group_key_2')
        ->and($groupSettings['group_key_1'])->toBe('value1')
        ->and($groupSettings['group_key_2'])->toBe('value2');
});

test('clears cache when setting is updated', function (): void {
    SystemSetting::set('cached_key', 'original_value', 'test');

    // First retrieval caches the value
    $value1 = SystemSetting::get('cached_key');
    expect($value1)->toBe('original_value');

    // Update the value
    SystemSetting::set('cached_key', 'new_value', 'test');

    // Should get the new value (cache cleared)
    $value2 = SystemSetting::get('cached_key');
    expect($value2)->toBe('new_value');
});

test('can update existing setting', function (): void {
    SystemSetting::set('update_key', 'initial', 'test');
    expect(SystemSetting::get('update_key'))->toBe('initial');

    SystemSetting::set('update_key', 'updated', 'test');
    expect(SystemSetting::get('update_key'))->toBe('updated');

    // Should still only have one record
    $count = SystemSetting::where('key', 'update_key')->count();
    expect($count)->toBe(1);
});

test('handles empty array storage', function (): void {
    SystemSetting::set('empty_array', [], 'test');

    $value = SystemSetting::get('empty_array');

    expect($value)->toBeArray()
        ->and($value)->toBeEmpty();
});

test('handles nested array storage', function (): void {
    $nested = [
        'level1' => [
            'level2' => [
                'value' => 'deep',
            ],
        ],
    ];

    SystemSetting::set('nested_array', $nested, 'test');

    $value = SystemSetting::get('nested_array');

    expect($value)->toBeArray()
        ->and($value['level1']['level2']['value'])->toBe('deep');
});
