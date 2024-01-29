<?php

class TCrypto
{
    public static function encrypt($pure_string, $encryption_key)
    {
        $cipher     = 'AES-256-CBC';
        $options    = OPENSSL_RAW_DATA;
        $hash_algo  = 'sha256';
        $sha2len    = 32;
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($pure_string, $cipher, $encryption_key, $options, $iv);
        $hmac = hash_hmac($hash_algo, $ciphertext_raw, $encryption_key, true);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    public static function decrypt($encrypted_string, $encryption_key)
    {
        $encrypted_string = base64_decode($encrypted_string);
        $cipher     = 'AES-256-CBC';
        $options    = OPENSSL_RAW_DATA;
        $hash_algo  = 'sha256';
        $sha2len    = 32;
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($encrypted_string, 0, $ivlen);
        $hmac = substr($encrypted_string, $ivlen, $sha2len);
        $ciphertext_raw = substr($encrypted_string, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $encryption_key, $options, $iv);
        $calcmac = hash_hmac($hash_algo, $ciphertext_raw, $encryption_key, true);
        if (function_exists('hash_equals')) {
            if (hash_equals($hmac, $calcmac)) return $original_plaintext;
        } else {
            if (self::hash_equals_custom($hmac, $calcmac)) return $original_plaintext;
        }
    }
   
    
    public static function hash_equals_custom($knownString, $userString)
    {
        if (function_exists('mb_strlen')) {
            $kLen = mb_strlen($knownString, '8bit');
            $uLen = mb_strlen($userString, '8bit');
        } else {
            $kLen = strlen($knownString);
            $uLen = strlen($userString);
        }
        if ($kLen !== $uLen) {
            return false;
        }
        $result = 0;
        for ($i = 0; $i < $kLen; $i++) {
            $result |= (ord($knownString[$i]) ^ ord($userString[$i]));
        }
        return 0 === $result;
    }
}
