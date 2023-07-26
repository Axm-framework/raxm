<?php

namespace Axm\Raxm;

class ComponentCheckSum
{

    /**
     * 
     */
    public static function generate($fingerprint, $memo)
    {

        // It's actually Ok if the "children" tracking is tampered with.
        // Also, this way JavaScript can modify children as it needs to for
        // dom-diffing purposes.
        $memoSansChildren = array_diff_key($memo ?? [], array_flip(['children']));

        $stringForHashing = ''
            . json_encode($fingerprint)
            . json_encode($memoSansChildren);

        $hashKey = static::getKey();

        return hash_hmac('sha256', $stringForHashing, $hashKey);
    }

    /**
     * 
     */
    protected static function getKey(): string
    {
        $random = random_bytes(32);
        return $random;
    }

    /**
     * 
     */
    public static function check($checksum, $fingerprint, $memo)
    {
        return hash_equals(static::generate($fingerprint, $memo), $checksum ?? '');
    }
}
