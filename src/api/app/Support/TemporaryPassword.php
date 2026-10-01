<?php

namespace App\Support;

/**
 * Generates one-time passwords for new accounts and administrator resets.
 *
 * 16 characters with at least one upper case letter, lower case letter,
 * digit and symbol (meets Password::defaults()). Look-alike characters
 * (0/O, 1/l/I) are left out so the password can be read out loud.
 */
final class TemporaryPassword
{
    private const UPPER = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const LOWER = 'abcdefghijkmnopqrstuvwxyz';

    private const DIGITS = '23456789';

    private const SYMBOLS = '!@#$%*-_+?';

    public static function generate(int $length = 16): string
    {
        $sets = [self::UPPER, self::LOWER, self::DIGITS, self::SYMBOLS];
        $all = implode('', $sets);

        $chars = array_map(fn (string $set) => self::pick($set), $sets);
        while (count($chars) < $length) {
            $chars[] = self::pick($all);
        }

        // Fisher-Yates shuffle with a CSPRNG.
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    private static function pick(string $set): string
    {
        return $set[random_int(0, strlen($set) - 1)];
    }
}
