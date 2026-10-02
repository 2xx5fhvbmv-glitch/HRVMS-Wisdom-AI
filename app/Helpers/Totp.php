<?php

namespace App\Helpers;

/**
 * RFC 6238 TOTP (30s step, 6 digits, SHA-1) — what Google Authenticator,
 * Authy and 1Password all speak. Used for super-admin MFA only.
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(): string
    {
        $secret = '';
        foreach (str_split(random_bytes(32)) as $byte) {
            $secret .= self::ALPHABET[ord($byte) & 31];
        }
        return $secret; // 32 base32 chars = 160 bits
    }

    public static function code(string $secret, int $timeSlice): string
    {
        $key = self::base32Decode($secret);
        $hash = hash_hmac('sha1', pack('J', $timeSlice), $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff;
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /** Accepts ±1 step of clock drift. */
    public static function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $slice = intdiv(time(), 30);
        foreach ([-1, 0, 1] as $drift) {
            if (hash_equals(self::code($secret, $slice + $drift), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function otpauthUri(string $secret, string $account, string $issuer = 'Wisdom Admin'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&digits=6&period=30';
    }

    private static function base32Decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper($secret)) as $char) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }
        return $bytes;
    }
}
