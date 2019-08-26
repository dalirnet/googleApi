<?php

namespace phpseclib\System\SSH;

use phpseclib\Crypt\RSA;
use phpseclib\System\SSH\Agent\Identity;

class Agent
{
    const SSH_AGENTC_REQUEST_IDENTITIES = 11;
    const SSH_AGENT_IDENTITIES_ANSWER = 12;
    const SSH_AGENTC_SIGN_REQUEST = 13;
    const SSH_AGENT_SIGN_RESPONSE = 14;
    const FORWARD_NONE = 0;
    const FORWARD_REQUEST = 1;
    const FORWARD_ACTIVE = 2;
    const SSH_AGENT_FAILURE = 5;
    var $fsock;
    var $forward_status = self::FORWARD_NONE;
    var $socket_buffer = '';
    var $expected_bytes = 0;

    function __construct()
    {
        switch (true) {
            case isset($_SERVER['SSH_AUTH_SOCK']):
                $address = $_SERVER['SSH_AUTH_SOCK'];
                break;
            case isset($_ENV['SSH_AUTH_SOCK']):
                $address = $_ENV['SSH_AUTH_SOCK'];
                break;
            default:
                user_error('SSH_AUTH_SOCK not found');
                return false;
        }
        $this->fsock = fsockopen('unix://' . $address, 0, $errno, $errstr);
        if (!$this->fsock) {
            user_error("Unable to connect to ssh-agent (Error $errno: $errstr)");
        }
    }

    function requestIdentities()
    {
        if (!$this->fsock) {
            return array();
        }
        $packet = pack('NC', 1, self::SSH_AGENTC_REQUEST_IDENTITIES);
        if (strlen($packet) != fputs($this->fsock, $packet)) {
            user_error('Connection closed while requesting identities');
        }
        $length = current(unpack('N', fread($this->fsock, 4)));
        $type = ord(fread($this->fsock, 1));
        if ($type != self::SSH_AGENT_IDENTITIES_ANSWER) {
            user_error('Unable to request identities');
        }
        $identities = array();
        $keyCount = current(unpack('N', fread($this->fsock, 4)));
        for ($i = 0; $i < $keyCount; $i++) {
            $length = current(unpack('N', fread($this->fsock, 4)));
            $key_blob = fread($this->fsock, $length);
            $key_str = 'ssh-rsa ' . base64_encode($key_blob);
            $length = current(unpack('N', fread($this->fsock, 4)));
            if ($length) {
                $key_str .= ' ' . fread($this->fsock, $length);
            }
            $length = current(unpack('N', substr($key_blob, 0, 4)));
            $key_type = substr($key_blob, 4, $length);
            switch ($key_type) {
                case 'ssh-rsa':
                    $key = new RSA();
                    $key->loadKey($key_str);
                    break;
                case 'ssh-dss':
                    break;
            }
            if (isset($key)) {
                $identity = new Identity($this->fsock);
                $identity->setPublicKey($key);
                $identity->setPublicKeyBlob($key_blob);
                $identities[] = $identity;
                unset($key);
            }
        }
        return $identities;
    }

    function startSSHForwarding($ssh)
    {
        if ($this->forward_status == self::FORWARD_NONE) {
            $this->forward_status = self::FORWARD_REQUEST;
        }
    }

    function _on_channel_open($ssh)
    {
        if ($this->forward_status == self::FORWARD_REQUEST) {
            $this->_request_forwarding($ssh);
        }
    }

    function _request_forwarding($ssh)
    {
        $request_channel = $ssh->_get_open_channel();
        if ($request_channel === false) {
            return false;
        }
        $packet = pack('CNNa*C', NET_SSH2_MSG_CHANNEL_REQUEST, $ssh->server_channels[$request_channel], strlen('auth-agent-req@openssh.com'), 'auth-agent-req@openssh.com', 1);
        $ssh->channel_status[$request_channel] = NET_SSH2_MSG_CHANNEL_REQUEST;
        if (!$ssh->_send_binary_packet($packet)) {
            return false;
        }
        $response = $ssh->_get_channel_packet($request_channel);
        if ($response === false) {
            return false;
        }
        $ssh->channel_status[$request_channel] = NET_SSH2_MSG_CHANNEL_OPEN;
        $this->forward_status = self::FORWARD_ACTIVE;
        return true;
    }

    function _forward_data($data)
    {
        if ($this->expected_bytes > 0) {
            $this->socket_buffer .= $data;
            $this->expected_bytes -= strlen($data);
        } else {
            $agent_data_bytes = current(unpack('N', $data));
            $current_data_bytes = strlen($data);
            $this->socket_buffer = $data;
            if ($current_data_bytes != $agent_data_bytes + 4) {
                $this->expected_bytes = ($agent_data_bytes + 4) - $current_data_bytes;
                return false;
            }
        }
        if (strlen($this->socket_buffer) != fwrite($this->fsock, $this->socket_buffer)) {
            user_error('Connection closed attempting to forward data to SSH agent');
        }
        $this->socket_buffer = '';
        $this->expected_bytes = 0;
        $agent_reply_bytes = current(unpack('N', fread($this->fsock, 4)));
        $agent_reply_data = fread($this->fsock, $agent_reply_bytes);
        $agent_reply_data = current(unpack('a*', $agent_reply_data));
        return pack('Na*', $agent_reply_bytes, $agent_reply_data);
    }
}
