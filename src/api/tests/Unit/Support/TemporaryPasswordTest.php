<?php

use App\Support\TemporaryPassword;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

it('is 16 characters by default', function () {
    expect(strlen(TemporaryPassword::generate()))->toBe(16);
});

it('honours a custom length', function (int $length) {
    expect(strlen(TemporaryPassword::generate($length)))->toBe($length);
})->with([12, 24]);

it('always meets the password policy', function () {
    foreach (range(1, 200) as $_) {
        $password = TemporaryPassword::generate();

        expect(Validator::make(['p' => $password], ['p' => Password::default()])->passes())->toBeTrue();
    }
});

it('contains every character class', function () {
    foreach (range(1, 200) as $_) {
        expect(TemporaryPassword::generate())
            ->toMatch('/[A-Z]/')
            ->toMatch('/[a-z]/')
            ->toMatch('/[0-9]/')
            ->toMatch('/[^A-Za-z0-9]/');
    }
});

it('leaves out look-alike characters', function () {
    $sample = implode('', array_map(fn () => TemporaryPassword::generate(), range(1, 200)));

    expect($sample)->not->toMatch('/[0O1lI]/');
});

it('does not repeat', function () {
    $passwords = array_map(fn () => TemporaryPassword::generate(), range(1, 500));

    expect(array_unique($passwords))->toHaveCount(500);
});
