<?php

namespace phpseclib\System\SSH\Agent;

use phpseclib\System\SSH\Agent;

class Identity
{
    var $key;
    var $key_blob;
    var $fsock;

    function __construct($fsock)
    {
        $this->fsock = $fsock;
    }

    function setPublicKey($key)
    {
        $this->key = $key;
        $this->key->setPublicKey();
    }

    function setPublicKeyBlob($key_blob)
    {
        $this->key_blob = $key_blob;
    }

    function getPublicKey($format = null)
    {
        return !isset($format) ? $this->key->getPublicKey() : $this->key->getPublicKey($format);
    }

    function setSignatureMode($mode)
    {
    }

    function sign($message)
    {

        $packet = pack('CNa*Na*N', Agent::SSH_AGENTC_SIGN_REQUEST, strlen($this->key_blob), $this->key_blob, strlen($message), $message, 0);
        $packet = pack('Na*', strlen($packet), $packet);
        if (strlen($packet) != fputs($this->fsock, $packet)) {
            user_error('Connection closed during signing');
        }
        $length = current(unpack('N', fread($this->fsock, 4)));
        $type = ord(fread($this->fsock, 1));
        if ($type != Agent::SSH_AGENT_SIGN_RESPONSE) {
            user_error('Unable to retrieve signature');
        }
        $signature_blob = fread($this->fsock, $length - 1);
        return substr($signature_blob, strlen('ssh-rsa') + 12);
    }
}
