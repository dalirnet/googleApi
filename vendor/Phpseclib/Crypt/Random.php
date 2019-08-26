<?php

namespace phpseclib\Crypt;

class Random
{
    static function string($length)
    {
        if (!$length) {
            return '';
        }
        if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
            try {
                return \random_bytes($length);
            } catch (\Throwable $e) {

            }
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

            if (extension_loaded('mcrypt') && function_exists('class_alias')) {
                return @mcrypt_create_iv($length);
            }
            if (extension_loaded('openssl') && version_compare(PHP_VERSION, '5.3.4', '>=')) {
                return openssl_random_pseudo_bytes($length);
            }
        } else {

            if (extension_loaded('openssl')) {
                return openssl_random_pseudo_bytes($length);
            }
            static $fp = true;
            if ($fp === true) {

                $fp = @fopen('/dev/urandom', 'rb');
            }
            if ($fp !== true && $fp !== false) {
                return fread($fp, $length);
            }
            if (extension_loaded('mcrypt')) {
                return @mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
            }
        }
        static $crypto = false, $v;
        if ($crypto === false) {

            $old_session_id = session_id();
            $old_use_cookies = ini_get('session.use_cookies');
            $old_session_cache_limiter = session_cache_limiter();
            $_OLD_SESSION = isset($_SESSION) ? $_SESSION : false;
            if ($old_session_id != '') {
                session_write_close();
            }
            session_id(1);
            ini_set('session.use_cookies', 0);
            session_cache_limiter('');
            session_start();
            $v = $seed = $_SESSION['seed'] = pack('H*', sha1((isset($_SERVER) ? phpseclib_safe_serialize($_SERVER) : '') . (isset($_POST) ? phpseclib_safe_serialize($_POST) : '') . (isset($_GET) ? phpseclib_safe_serialize($_GET) : '') . (isset($_COOKIE) ? phpseclib_safe_serialize($_COOKIE) : '') . phpseclib_safe_serialize($GLOBALS) . phpseclib_safe_serialize($_SESSION) . phpseclib_safe_serialize($_OLD_SESSION)));
            if (!isset($_SESSION['count'])) {
                $_SESSION['count'] = 0;
            }
            $_SESSION['count']++;
            session_write_close();
            if ($old_session_id != '') {
                session_id($old_session_id);
                session_start();
                ini_set('session.use_cookies', $old_use_cookies);
                session_cache_limiter($old_session_cache_limiter);
            } else {
                if ($_OLD_SESSION !== false) {
                    $_SESSION = $_OLD_SESSION;
                    unset($_OLD_SESSION);
                } else {
                    unset($_SESSION);
                }
            }
            $key = pack('H*', sha1($seed . 'A'));
            $iv = pack('H*', sha1($seed . 'C'));
            switch (true) {
                case class_exists('\phpseclib\Crypt\AES'):
                    $crypto = new AES(Base::MODE_CTR);
                    break;
                case class_exists('\phpseclib\Crypt\Twofish'):
                    $crypto = new Twofish(Base::MODE_CTR);
                    break;
                case class_exists('\phpseclib\Crypt\Blowfish'):
                    $crypto = new Blowfish(Base::MODE_CTR);
                    break;
                case class_exists('\phpseclib\Crypt\TripleDES'):
                    $crypto = new TripleDES(Base::MODE_CTR);
                    break;
                case class_exists('\phpseclib\Crypt\DES'):
                    $crypto = new DES(Base::MODE_CTR);
                    break;
                case class_exists('\phpseclib\Crypt\RC4'):
                    $crypto = new RC4();
                    break;
                default:
                    user_error(__CLASS__ . ' requires at least one symmetric cipher be loaded');
                    return false;
            }
            $crypto->setKey($key);
            $crypto->setIV($iv);
            $crypto->enableContinuousBuffer();
        }
        $result = '';
        while (strlen($result) < $length) {
            $i = $crypto->encrypt(microtime());
            $r = $crypto->encrypt($i ^ $v);
            $v = $crypto->encrypt($r ^ $i);
            $result .= $r;
        }
        return substr($result, 0, $length);
    }
}

if (!function_exists('phpseclib_safe_serialize')) {

    function phpseclib_safe_serialize(&$arr)
    {
        if (is_object($arr)) {
            return '';
        }
        if (!is_array($arr)) {
            return serialize($arr);
        }
        if (isset($arr['__phpseclib_marker'])) {
            return '';
        }
        $safearr = array();
        $arr['__phpseclib_marker'] = true;
        foreach (array_keys($arr) as $key) {

            if ($key !== '__phpseclib_marker') {
                $safearr[$key] = phpseclib_safe_serialize($arr[$key]);
            }
        }
        unset($arr['__phpseclib_marker']);
        return serialize($safearr);
    }
}
