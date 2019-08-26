<?php

namespace phpseclib\Crypt;

class AES extends Rijndael
{
    function setBlockLength($length)
    {
        return;
    }

    function setKeyLength($length)
    {
        switch ($length) {
            case 160:
                $length = 192;
                break;
            case 224:
                $length = 256;
        }
        parent::setKeyLength($length);
    }

    function setKey($key)
    {
        parent::setKey($key);
        if (!$this->explicit_key_length) {
            $length = strlen($key);
            switch (true) {
                case $length <= 16:
                    $this->key_length = 16;
                    break;
                case $length <= 24:
                    $this->key_length = 24;
                    break;
                default:
                    $this->key_length = 32;
            }
            $this->_setEngine();
        }
    }
}
