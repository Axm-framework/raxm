<?php

namespace Axm\Raxm;

/**
 * Class ComponentCheckSum
 *
 * This class generate a hash using HMAC-SHA256.
 *
 * @package Axm\Raxm
 */

class ComponentCheckSum
{

    /**
     * Generates a hash using HMAC-SHA256 based on the provided fingerprint and memo.
     */
    public static function generate(array $fingerprint, ?array $memo = null): string
    {
        // Exclude the 'children' key from the memo, if present.
        $memoSansChildren = array_diff_key($memo ?? [], array_flip(['children']));

        // Create a string for hashing by encoding the fingerprint and the memo without 'children'.
        $stringForHashing = json_encode($fingerprint) . json_encode($memoSansChildren);

        // Generate the hash using HMAC-SHA256 with a 'secret' key.
        return hash_hmac('sha256', $stringForHashing, 'secret');
    }

    /**
     * Checks if a given checksum matches the expected checksum based on the provided fingerprint and memo.
     */
    public static function check(?string $checksum, array $fingerprint, ?array $memo = null): bool
    {
        // Use the static generate method to generate the expected checksum.
        $expectedChecksum = static::generate($fingerprint, $memo);

        return hash_equals($expectedChecksum, $checksum ?? '');
    }

}
