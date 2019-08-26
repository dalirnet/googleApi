<?php

namespace phpseclib\Net;

class SCP
{
    const SOURCE_LOCAL_FILE = 1;
    const SOURCE_STRING = 2;
    const MODE_SSH1 = 1;
    const MODE_SSH2 = 2;
    var $ssh;
    var $packet_size;
    var $mode;

    function __construct($ssh)
    {
        if ($ssh instanceof SSH2) {
            $this->mode = self::MODE_SSH2;
        } elseif ($ssh instanceof SSH1) {
            $this->packet_size = 50000;
            $this->mode = self::MODE_SSH1;
        } else {
            return;
        }
        $this->ssh = $ssh;
    }

    function put($remote_file, $data, $mode = self::SOURCE_STRING, $callback = null)
    {
        if (!isset($this->ssh)) {
            return false;
        }
        if (!$this->ssh->exec('scp -t ' . escapeshellarg($remote_file), false)) {
            return false;
        }
        $temp = $this->_receive();
        if ($temp !== chr(0)) {
            return false;
        }
        if ($this->mode == self::MODE_SSH2) {
            $this->packet_size = $this->ssh->packet_size_client_to_server[SSH2::CHANNEL_EXEC] - 4;
        }
        $remote_file = basename($remote_file);
        if ($mode == self::SOURCE_STRING) {
            $size = strlen($data);
        } else {
            if (!is_file($data)) {
                user_error("$data is not a valid file", E_USER_NOTICE);
                return false;
            }
            $fp = @fopen($data, 'rb');
            if (!$fp) {
                return false;
            }
            $size = filesize($data);
        }
        $this->_send('C0644 ' . $size . ' ' . $remote_file . "\n");
        $temp = $this->_receive();
        if ($temp !== chr(0)) {
            return false;
        }
        $sent = 0;
        while ($sent < $size) {
            $temp = $mode & self::SOURCE_STRING ? substr($data, $sent, $this->packet_size) : fread($fp, $this->packet_size);
            $this->_send($temp);
            $sent += strlen($temp);
            if (is_callable($callback)) {
                call_user_func($callback, $sent);
            }
        }
        $this->_close();
        if ($mode != self::SOURCE_STRING) {
            fclose($fp);
        }
        return true;
    }

    function _receive()
    {
        switch ($this->mode) {
            case self::MODE_SSH2:
                return $this->ssh->_get_channel_packet(SSH2::CHANNEL_EXEC, true);
            case self::MODE_SSH1:
                if (!$this->ssh->bitmap) {
                    return false;
                }
                while (true) {
                    $response = $this->ssh->_get_binary_packet();
                    switch ($response[SSH1::RESPONSE_TYPE]) {
                        case NET_SSH1_SMSG_STDOUT_DATA:
                            if (strlen($response[SSH1::RESPONSE_DATA]) < 4) {
                                return false;
                            }
                            extract(unpack('Nlength', $response[SSH1::RESPONSE_DATA]));
                            return $this->ssh->_string_shift($response[SSH1::RESPONSE_DATA], $length);
                        case NET_SSH1_SMSG_STDERR_DATA:
                            break;
                        case NET_SSH1_SMSG_EXITSTATUS:
                            $this->ssh->_send_binary_packet(chr(NET_SSH1_CMSG_EXIT_CONFIRMATION));
                            fclose($this->ssh->fsock);
                            $this->ssh->bitmap = 0;
                            return false;
                        default:
                            user_error('Unknown packet received', E_USER_NOTICE);
                            return false;
                    }
                }
        }
    }

    function _send($data)
    {
        switch ($this->mode) {
            case self::MODE_SSH2:
                $this->ssh->_send_channel_packet(SSH2::CHANNEL_EXEC, $data);
                break;
            case self::MODE_SSH1:
                $data = pack('CNa*', NET_SSH1_CMSG_STDIN_DATA, strlen($data), $data);
                $this->ssh->_send_binary_packet($data);
        }
    }

    function _close()
    {
        switch ($this->mode) {
            case self::MODE_SSH2:
                $this->ssh->_close_channel(SSH2::CHANNEL_EXEC, true);
                break;
            case self::MODE_SSH1:
                $this->ssh->disconnect();
        }
    }

    function get($remote_file, $local_file = false)
    {
        if (!isset($this->ssh)) {
            return false;
        }
        if (!$this->ssh->exec('scp -f ' . escapeshellarg($remote_file), false)) {
            return false;
        }
        $this->_send("\0");
        if (!preg_match('#(?<perms>[^ ]+) (?<size>\d+) (?<name>.+)#', rtrim($this->_receive()), $info)) {
            return false;
        }
        $this->_send("\0");
        $size = 0;
        if ($local_file !== false) {
            $fp = @fopen($local_file, 'wb');
            if (!$fp) {
                return false;
            }
        }
        $content = '';
        while ($size < $info['size']) {
            $data = $this->_receive();
            $size += strlen($data);
            if ($local_file === false) {
                $content .= $data;
            } else {
                fputs($fp, $data);
            }
        }
        $this->_close();
        if ($local_file !== false) {
            fclose($fp);
            return true;
        }
        return $content;
    }
}
