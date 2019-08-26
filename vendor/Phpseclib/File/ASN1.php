<?php

namespace phpseclib\File;

use DateTime;
use DateTimeZone;
use phpseclib\File\ASN1\Element;
use phpseclib\Math\BigInteger;

class ASN1
{
    const CLASS_UNIVERSAL = 0;
    const CLASS_APPLICATION = 1;
    const CLASS_CONTEXT_SPECIFIC = 2;
    const CLASS_PRIVATE = 3;
    const TYPE_BOOLEAN = 1;
    const TYPE_INTEGER = 2;
    const TYPE_BIT_STRING = 3;
    const TYPE_OCTET_STRING = 4;
    const TYPE_NULL = 5;
    const TYPE_OBJECT_IDENTIFIER = 6;
    const TYPE_REAL = 9;
    const TYPE_ENUMERATED = 10;
    const TYPE_UTF8_STRING = 12;
    const TYPE_SEQUENCE = 16;
    const TYPE_SET = 17;
    const TYPE_NUMERIC_STRING = 18;
    const TYPE_PRINTABLE_STRING = 19;
    const TYPE_TELETEX_STRING = 20;
    const TYPE_VIDEOTEX_STRING = 21;
    const TYPE_IA5_STRING = 22;
    const TYPE_UTC_TIME = 23;
    const TYPE_GENERALIZED_TIME = 24;
    const TYPE_GRAPHIC_STRING = 25;
    const TYPE_VISIBLE_STRING = 26;
    const TYPE_GENERAL_STRING = 27;
    const TYPE_UNIVERSAL_STRING = 28;
    const TYPE_BMP_STRING = 30;
    const TYPE_CHOICE = -1;
    const TYPE_ANY = -2;
    var $oids = array();
    var $format = 'D, d M Y H:i:s O';
    var $encoded;
    var $filters;
    var $ANYmap = array(self::TYPE_BOOLEAN => true, self::TYPE_INTEGER => true, self::TYPE_BIT_STRING => 'bitString', self::TYPE_OCTET_STRING => 'octetString', self::TYPE_NULL => 'null', self::TYPE_OBJECT_IDENTIFIER => 'objectIdentifier', self::TYPE_REAL => true, self::TYPE_ENUMERATED => 'enumerated', self::TYPE_UTF8_STRING => 'utf8String', self::TYPE_NUMERIC_STRING => 'numericString', self::TYPE_PRINTABLE_STRING => 'printableString', self::TYPE_TELETEX_STRING => 'teletexString', self::TYPE_VIDEOTEX_STRING => 'videotexString', self::TYPE_IA5_STRING => 'ia5String', self::TYPE_UTC_TIME => 'utcTime', self::TYPE_GENERALIZED_TIME => 'generalTime', self::TYPE_GRAPHIC_STRING => 'graphicString', self::TYPE_VISIBLE_STRING => 'visibleString', self::TYPE_GENERAL_STRING => 'generalString', self::TYPE_UNIVERSAL_STRING => 'universalString', self::TYPE_BMP_STRING => 'bmpString');
    var $stringTypeSize = array(self::TYPE_UTF8_STRING => 0, self::TYPE_BMP_STRING => 2, self::TYPE_UNIVERSAL_STRING => 4, self::TYPE_PRINTABLE_STRING => 1, self::TYPE_TELETEX_STRING => 1, self::TYPE_IA5_STRING => 1, self::TYPE_VISIBLE_STRING => 1,);

    function decodeBER($encoded)
    {
        if ($encoded instanceof Element) {
            $encoded = $encoded->element;
        }
        $this->encoded = $encoded;
        return array($this->_decode_ber($encoded));
    }

    function _decode_ber($encoded, $start = 0, $encoded_pos = 0)
    {
        $current = array('start' => $start);
        $type = ord($encoded[$encoded_pos++]);
        $start++;
        $constructed = ($type >> 5) & 1;
        $tag = $type & 0x1F;
        if ($tag == 0x1F) {
            $tag = 0;
            do {
                $loop = ord($encoded[0]) >> 7;
                $tag <<= 7;
                $tag |= ord($encoded[$encoded_pos++]) & 0x7F;
                $start++;
            } while ($loop);
        }
        $length = ord($encoded[$encoded_pos++]);
        $start++;
        if ($length == 0x80) {

            $length = strlen($encoded) - $encoded_pos;
        } elseif ($length & 0x80) {

            $length &= 0x7F;
            $temp = substr($encoded, $encoded_pos, $length);
            $encoded_pos += $length;
            $current += array('headerlength' => $length + 2);
            $start += $length;
            extract(unpack('Nlength', substr(str_pad($temp, 4, chr(0), STR_PAD_LEFT), -4)));
        } else {
            $current += array('headerlength' => 2);
        }
        if ($length > (strlen($encoded) - $encoded_pos)) {
            return false;
        }
        $content = substr($encoded, $encoded_pos, $length);
        $content_pos = 0;
        $class = ($type >> 6) & 3;
        switch ($class) {
            case self::CLASS_APPLICATION:
            case self::CLASS_PRIVATE:
            case self::CLASS_CONTEXT_SPECIFIC:
                if (!$constructed) {
                    return array('type' => $class, 'constant' => $tag, 'content' => $content, 'length' => $length + $start - $current['start']);
                }
                $newcontent = array();
                $remainingLength = $length;
                while ($remainingLength > 0) {
                    $temp = $this->_decode_ber($content, $start, $content_pos);
                    $length = $temp['length'];
                    if (substr($content, $content_pos + $length, 2) == "\0\0") {
                        $length += 2;
                        $start += $length;
                        $newcontent[] = $temp;
                        break;
                    }
                    $start += $length;
                    $remainingLength -= $length;
                    $newcontent[] = $temp;
                    $content_pos += $length;
                }
                return array('type' => $class, 'constant' => $tag, 'content' => $newcontent, 'length' => $start - $current['start']) + $current;
        }
        $current += array('type' => $tag);
        switch ($tag) {
            case self::TYPE_BOOLEAN:
                $current['content'] = (bool)ord($content[$content_pos]);
                break;
            case self::TYPE_INTEGER:
            case self::TYPE_ENUMERATED:
                $current['content'] = new BigInteger(substr($content, $content_pos), -256);
                break;
            case self::TYPE_REAL:
                return false;
            case self::TYPE_BIT_STRING:
                if (!$constructed) {
                    $current['content'] = substr($content, $content_pos);
                } else {
                    $temp = $this->_decode_ber($content, $start, $content_pos);
                    $length -= (strlen($content) - $content_pos);
                    $last = count($temp) - 1;
                    for ($i = 0; $i < $last; $i++) {

                        $current['content'] .= substr($temp[$i]['content'], 1);
                    }
                    $current['content'] = $temp[$last]['content'][0] . $current['content'] . substr($temp[$i]['content'], 1);
                }
                break;
            case self::TYPE_OCTET_STRING:
                if (!$constructed) {
                    $current['content'] = substr($content, $content_pos);
                } else {
                    $current['content'] = '';
                    $length = 0;
                    while (substr($content, $content_pos, 2) != "\0\0") {
                        $temp = $this->_decode_ber($content, $length + $start, $content_pos);
                        $content_pos += $temp['length'];
                        $current['content'] .= $temp['content'];
                        $length += $temp['length'];
                    }
                    if (substr($content, $content_pos, 2) == "\0\0") {
                        $length += 2;
                    }
                }
                break;
            case self::TYPE_NULL:
                break;
            case self::TYPE_SEQUENCE:
            case self::TYPE_SET:
                $offset = 0;
                $current['content'] = array();
                $content_len = strlen($content);
                while ($content_pos < $content_len) {

                    if (!isset($current['headerlength']) && substr($content, $content_pos, 2) == "\0\0") {
                        $length = $offset + 2;
                        break 2;
                    }
                    $temp = $this->_decode_ber($content, $start + $offset, $content_pos);
                    $content_pos += $temp['length'];
                    $current['content'][] = $temp;
                    $offset += $temp['length'];
                }
                break;
            case self::TYPE_OBJECT_IDENTIFIER:
                $temp = ord($content[$content_pos++]);
                $current['content'] = sprintf('%d.%d', floor($temp / 40), $temp % 40);
                $valuen = 0;
                $content_len = strlen($content);
                while ($content_pos < $content_len) {
                    $temp = ord($content[$content_pos++]);
                    $valuen <<= 7;
                    $valuen |= $temp & 0x7F;
                    if (~$temp & 0x80) {
                        $current['content'] .= ".$valuen";
                        $valuen = 0;
                    }
                }
                break;
            case self::TYPE_NUMERIC_STRING:
            case self::TYPE_PRINTABLE_STRING:
            case self::TYPE_TELETEX_STRING:
            case self::TYPE_VIDEOTEX_STRING:
            case self::TYPE_VISIBLE_STRING:
            case self::TYPE_IA5_STRING:
            case self::TYPE_GRAPHIC_STRING:
            case self::TYPE_GENERAL_STRING:
            case self::TYPE_UTF8_STRING:
            case self::TYPE_BMP_STRING:
                $current['content'] = substr($content, $content_pos);
                break;
            case self::TYPE_UTC_TIME:
            case self::TYPE_GENERALIZED_TIME:
                $current['content'] = $this->_decodeTime(substr($content, $content_pos), $tag);
            default:
        }
        $start += $length;
        return $current + array('length' => $start - $current['start']);
    }

    function _decodeTime($content, $tag)
    {

        $format = 'YmdHis';
        if ($tag == self::TYPE_UTC_TIME) {

            if (preg_match('#^(\d{10})(Z|[+-]\d{4})$#', $content, $matches)) {
                $content = $matches[1] . '00' . $matches[2];
            }
            $prefix = substr($content, 0, 2) >= 50 ? '19' : '20';
            $content = $prefix . $content;
        } elseif (strpos($content, '.') !== false) {
            $format .= '.u';
        }
        if ($content[strlen($content) - 1] == 'Z') {
            $content = substr($content, 0, -1) . '+0000';
        }
        if (strpos($content, '-') !== false || strpos($content, '+') !== false) {
            $format .= 'O';
        }
        return @DateTime::createFromFormat($format, $content);
    }

    function asn1map($decoded, $mapping, $special = array())
    {
        if (isset($mapping['explicit']) && is_array($decoded['content'])) {
            $decoded = $decoded['content'][0];
        }
        switch (true) {
            case $mapping['type'] == self::TYPE_ANY:
                $intype = $decoded['type'];
                if (isset($decoded['constant']) || !isset($this->ANYmap[$intype]) || (ord($this->encoded[$decoded['start']]) & 0x20)) {
                    return new Element(substr($this->encoded, $decoded['start'], $decoded['length']));
                }
                $inmap = $this->ANYmap[$intype];
                if (is_string($inmap)) {
                    return array($inmap => $this->asn1map($decoded, array('type' => $intype) + $mapping, $special));
                }
                break;
            case $mapping['type'] == self::TYPE_CHOICE:
                foreach ($mapping['children'] as $key => $option) {
                    switch (true) {
                        case isset($option['constant']) && $option['constant'] == $decoded['constant']:
                        case !isset($option['constant']) && $option['type'] == $decoded['type']:
                            $value = $this->asn1map($decoded, $option, $special);
                            break;
                        case !isset($option['constant']) && $option['type'] == self::TYPE_CHOICE:
                            $v = $this->asn1map($decoded, $option, $special);
                            if (isset($v)) {
                                $value = $v;
                            }
                    }
                    if (isset($value)) {
                        if (isset($special[$key])) {
                            $value = call_user_func($special[$key], $value);
                        }
                        return array($key => $value);
                    }
                }
                return null;
            case isset($mapping['implicit']):
            case isset($mapping['explicit']):
            case $decoded['type'] == $mapping['type']:
                break;
            default:
                switch (true) {
                    case $decoded['type'] < 18:
                    case $decoded['type'] > 30:
                    case $mapping['type'] < 18:
                    case $mapping['type'] > 30:
                        return null;
                }
        }
        if (isset($mapping['implicit'])) {
            $decoded['type'] = $mapping['type'];
        }
        switch ($decoded['type']) {
            case self::TYPE_SEQUENCE:
                $map = array();
                if (isset($mapping['min']) && isset($mapping['max'])) {
                    $child = $mapping['children'];
                    foreach ($decoded['content'] as $content) {
                        if (($map[] = $this->asn1map($content, $child, $special)) === null) {
                            return null;
                        }
                    }
                    return $map;
                }
                $n = count($decoded['content']);
                $i = 0;
                foreach ($mapping['children'] as $key => $child) {
                    $maymatch = $i < $n;
                    if ($maymatch) {
                        $temp = $decoded['content'][$i];
                        if ($child['type'] != self::TYPE_CHOICE) {

                            $childClass = $tempClass = self::CLASS_UNIVERSAL;
                            $constant = null;
                            if (isset($temp['constant'])) {
                                $tempClass = $temp['type'];
                            }
                            if (isset($child['class'])) {
                                $childClass = $child['class'];
                                $constant = $child['cast'];
                            } elseif (isset($child['constant'])) {
                                $childClass = self::CLASS_CONTEXT_SPECIFIC;
                                $constant = $child['constant'];
                            }
                            if (isset($constant) && isset($temp['constant'])) {

                                $maymatch = $constant == $temp['constant'] && $childClass == $tempClass;
                            } else {

                                $maymatch = !isset($child['constant']) && array_search($child['type'], array($temp['type'], self::TYPE_ANY, self::TYPE_CHOICE)) !== false;
                            }
                        }
                    }
                    if ($maymatch) {

                        $candidate = $this->asn1map($temp, $child, $special);
                        $maymatch = $candidate !== null;
                    }
                    if ($maymatch) {

                        if (isset($special[$key])) {
                            $candidate = call_user_func($special[$key], $candidate);
                        }
                        $map[$key] = $candidate;
                        $i++;
                    } elseif (isset($child['default'])) {
                        $map[$key] = $child['default'];
                    } elseif (!isset($child['optional'])) {
                        return null;
                    }
                }
                return $i < $n ? null : $map;
            case self::TYPE_SET:
                $map = array();
                if (isset($mapping['min']) && isset($mapping['max'])) {
                    $child = $mapping['children'];
                    foreach ($decoded['content'] as $content) {
                        if (($map[] = $this->asn1map($content, $child, $special)) === null) {
                            return null;
                        }
                    }
                    return $map;
                }
                for ($i = 0; $i < count($decoded['content']); $i++) {
                    $temp = $decoded['content'][$i];
                    $tempClass = self::CLASS_UNIVERSAL;
                    if (isset($temp['constant'])) {
                        $tempClass = $temp['type'];
                    }
                    foreach ($mapping['children'] as $key => $child) {
                        if (isset($map[$key])) {
                            continue;
                        }
                        $maymatch = true;
                        if ($child['type'] != self::TYPE_CHOICE) {
                            $childClass = self::CLASS_UNIVERSAL;
                            $constant = null;
                            if (isset($child['class'])) {
                                $childClass = $child['class'];
                                $constant = $child['cast'];
                            } elseif (isset($child['constant'])) {
                                $childClass = self::CLASS_CONTEXT_SPECIFIC;
                                $constant = $child['constant'];
                            }
                            if (isset($constant) && isset($temp['constant'])) {

                                $maymatch = $constant == $temp['constant'] && $childClass == $tempClass;
                            } else {

                                $maymatch = !isset($child['constant']) && array_search($child['type'], array($temp['type'], self::TYPE_ANY, self::TYPE_CHOICE)) !== false;
                            }
                        }
                        if ($maymatch) {

                            $candidate = $this->asn1map($temp, $child, $special);
                            $maymatch = $candidate !== null;
                        }
                        if (!$maymatch) {
                            break;
                        }
                        if (isset($special[$key])) {
                            $candidate = call_user_func($special[$key], $candidate);
                        }
                        $map[$key] = $candidate;
                        break;
                    }
                }
                foreach ($mapping['children'] as $key => $child) {
                    if (!isset($map[$key])) {
                        if (isset($child['default'])) {
                            $map[$key] = $child['default'];
                        } elseif (!isset($child['optional'])) {
                            return null;
                        }
                    }
                }
                return $map;
            case self::TYPE_OBJECT_IDENTIFIER:
                return isset($this->oids[$decoded['content']]) ? $this->oids[$decoded['content']] : $decoded['content'];
            case self::TYPE_UTC_TIME:
            case self::TYPE_GENERALIZED_TIME:
                if (isset($mapping['implicit'])) {
                    $decoded['content'] = $this->_decodeTime($decoded['content'], $decoded['type']);
                }
                return $decoded['content'] ? $decoded['content']->format($this->format) : false;
            case self::TYPE_BIT_STRING:
                if (isset($mapping['mapping'])) {
                    $offset = ord($decoded['content'][0]);
                    $size = (strlen($decoded['content']) - 1) * 8 - $offset;
                    $bits = count($mapping['mapping']) == $size ? array() : array_fill(0, count($mapping['mapping']) - $size, false);
                    for ($i = strlen($decoded['content']) - 1; $i > 0; $i--) {
                        $current = ord($decoded['content'][$i]);
                        for ($j = $offset; $j < 8; $j++) {
                            $bits[] = (bool)($current & (1 << $j));
                        }
                        $offset = 0;
                    }
                    $values = array();
                    $map = array_reverse($mapping['mapping']);
                    foreach ($map as $i => $value) {
                        if ($bits[$i]) {
                            $values[] = $value;
                        }
                    }
                    return $values;
                }
            case self::TYPE_OCTET_STRING:
                return base64_encode($decoded['content']);
            case self::TYPE_NULL:
                return '';
            case self::TYPE_BOOLEAN:
                return $decoded['content'];
            case self::TYPE_NUMERIC_STRING:
            case self::TYPE_PRINTABLE_STRING:
            case self::TYPE_TELETEX_STRING:
            case self::TYPE_VIDEOTEX_STRING:
            case self::TYPE_IA5_STRING:
            case self::TYPE_GRAPHIC_STRING:
            case self::TYPE_VISIBLE_STRING:
            case self::TYPE_GENERAL_STRING:
            case self::TYPE_UNIVERSAL_STRING:
            case self::TYPE_UTF8_STRING:
            case self::TYPE_BMP_STRING:
                return $decoded['content'];
            case self::TYPE_INTEGER:
            case self::TYPE_ENUMERATED:
                $temp = $decoded['content'];
                if (isset($mapping['implicit'])) {
                    $temp = new BigInteger($decoded['content'], -256);
                }
                if (isset($mapping['mapping'])) {
                    $temp = (int)$temp->toString();
                    return isset($mapping['mapping'][$temp]) ? $mapping['mapping'][$temp] : false;
                }
                return $temp;
        }
    }

    function encodeDER($source, $mapping, $special = array())
    {
        $this->location = array();
        return $this->_encode_der($source, $mapping, null, $special);
    }

    function _encode_der($source, $mapping, $idx = null, $special = array())
    {
        if ($source instanceof Element) {
            return $source->element;
        }
        if (isset($mapping['default']) && $source === $mapping['default']) {
            return '';
        }
        if (isset($idx)) {
            if (isset($special[$idx])) {
                $source = call_user_func($special[$idx], $source);
            }
            $this->location[] = $idx;
        }
        $tag = $mapping['type'];
        switch ($tag) {
            case self::TYPE_SET:
            case self::TYPE_SEQUENCE:
                $tag |= 0x20;
                if (isset($mapping['min']) && isset($mapping['max'])) {
                    $value = array();
                    $child = $mapping['children'];
                    foreach ($source as $content) {
                        $temp = $this->_encode_der($content, $child, null, $special);
                        if ($temp === false) {
                            return false;
                        }
                        $value[] = $temp;
                    }
                    if ($mapping['type'] == self::TYPE_SET) {
                        sort($value);
                    }
                    $value = implode($value, '');
                    break;
                }
                $value = '';
                foreach ($mapping['children'] as $key => $child) {
                    if (!array_key_exists($key, $source)) {
                        if (!isset($child['optional'])) {
                            return false;
                        }
                        continue;
                    }
                    $temp = $this->_encode_der($source[$key], $child, $key, $special);
                    if ($temp === false) {
                        return false;
                    }
                    if ($temp === '') {
                        continue;
                    }
                    if (isset($child['constant'])) {

                        if (isset($child['explicit']) || $child['type'] == self::TYPE_CHOICE) {
                            $subtag = chr((self::CLASS_CONTEXT_SPECIFIC << 6) | 0x20 | $child['constant']);
                            $temp = $subtag . $this->_encodeLength(strlen($temp)) . $temp;
                        } else {
                            $subtag = chr((self::CLASS_CONTEXT_SPECIFIC << 6) | (ord($temp[0]) & 0x20) | $child['constant']);
                            $temp = $subtag . substr($temp, 1);
                        }
                    }
                    $value .= $temp;
                }
                break;
            case self::TYPE_CHOICE:
                $temp = false;
                foreach ($mapping['children'] as $key => $child) {
                    if (!isset($source[$key])) {
                        continue;
                    }
                    $temp = $this->_encode_der($source[$key], $child, $key, $special);
                    if ($temp === false) {
                        return false;
                    }
                    if ($temp === '') {
                        continue;
                    }
                    $tag = ord($temp[0]);
                    if (isset($child['constant'])) {
                        if (isset($child['explicit']) || $child['type'] == self::TYPE_CHOICE) {
                            $subtag = chr((self::CLASS_CONTEXT_SPECIFIC << 6) | 0x20 | $child['constant']);
                            $temp = $subtag . $this->_encodeLength(strlen($temp)) . $temp;
                        } else {
                            $subtag = chr((self::CLASS_CONTEXT_SPECIFIC << 6) | (ord($temp[0]) & 0x20) | $child['constant']);
                            $temp = $subtag . substr($temp, 1);
                        }
                    }
                }
                if (isset($idx)) {
                    array_pop($this->location);
                }
                if ($temp && isset($mapping['cast'])) {
                    $temp[0] = chr(($mapping['class'] << 6) | ($tag & 0x20) | $mapping['cast']);
                }
                return $temp;
            case self::TYPE_INTEGER:
            case self::TYPE_ENUMERATED:
                if (!isset($mapping['mapping'])) {
                    if (is_numeric($source)) {
                        $source = new BigInteger($source);
                    }
                    $value = $source->toBytes(true);
                } else {
                    $value = array_search($source, $mapping['mapping']);
                    if ($value === false) {
                        return false;
                    }
                    $value = new BigInteger($value);
                    $value = $value->toBytes(true);
                }
                if (!strlen($value)) {
                    $value = chr(0);
                }
                break;
            case self::TYPE_UTC_TIME:
            case self::TYPE_GENERALIZED_TIME:
                $format = $mapping['type'] == self::TYPE_UTC_TIME ? 'y' : 'Y';
                $format .= 'mdHis';
                $date = new DateTime($source, new DateTimeZone('GMT'));
                $value = $date->format($format) . 'Z';
                break;
            case self::TYPE_BIT_STRING:
                if (isset($mapping['mapping'])) {
                    $bits = array_fill(0, count($mapping['mapping']), 0);
                    $size = 0;
                    for ($i = 0; $i < count($mapping['mapping']); $i++) {
                        if (in_array($mapping['mapping'][$i], $source)) {
                            $bits[$i] = 1;
                            $size = $i;
                        }
                    }
                    if (isset($mapping['min']) && $mapping['min'] >= 1 && $size < $mapping['min']) {
                        $size = $mapping['min'] - 1;
                    }
                    $offset = 8 - (($size + 1) & 7);
                    $offset = $offset !== 8 ? $offset : 0;
                    $value = chr($offset);
                    for ($i = $size + 1; $i < count($mapping['mapping']); $i++) {
                        unset($bits[$i]);
                    }
                    $bits = implode('', array_pad($bits, $size + $offset + 1, 0));
                    $bytes = explode(' ', rtrim(chunk_split($bits, 8, ' ')));
                    foreach ($bytes as $byte) {
                        $value .= chr(bindec($byte));
                    }
                    break;
                }
            case self::TYPE_OCTET_STRING:
                $value = base64_decode($source);
                break;
            case self::TYPE_OBJECT_IDENTIFIER:
                $oid = preg_match('#(?:\d+\.)+#', $source) ? $source : array_search($source, $this->oids);
                if ($oid === false) {
                    user_error('Invalid OID');
                    return false;
                }
                $value = '';
                $parts = explode('.', $oid);
                $value = chr(40 * $parts[0] + $parts[1]);
                for ($i = 2; $i < count($parts); $i++) {
                    $temp = '';
                    if (!$parts[$i]) {
                        $temp = "\0";
                    } else {
                        while ($parts[$i]) {
                            $temp = chr(0x80 | ($parts[$i] & 0x7F)) . $temp;
                            $parts[$i] >>= 7;
                        }
                        $temp[strlen($temp) - 1] = $temp[strlen($temp) - 1] & chr(0x7F);
                    }
                    $value .= $temp;
                }
                break;
            case self::TYPE_ANY:
                $loc = $this->location;
                if (isset($idx)) {
                    array_pop($this->location);
                }
                switch (true) {
                    case !isset($source):
                        return $this->_encode_der(null, array('type' => self::TYPE_NULL) + $mapping, null, $special);
                    case is_int($source):
                    case $source instanceof BigInteger:
                        return $this->_encode_der($source, array('type' => self::TYPE_INTEGER) + $mapping, null, $special);
                    case is_float($source):
                        return $this->_encode_der($source, array('type' => self::TYPE_REAL) + $mapping, null, $special);
                    case is_bool($source):
                        return $this->_encode_der($source, array('type' => self::TYPE_BOOLEAN) + $mapping, null, $special);
                    case is_array($source) && count($source) == 1:
                        $typename = implode('', array_keys($source));
                        $outtype = array_search($typename, $this->ANYmap, true);
                        if ($outtype !== false) {
                            return $this->_encode_der($source[$typename], array('type' => $outtype) + $mapping, null, $special);
                        }
                }
                $filters = $this->filters;
                foreach ($loc as $part) {
                    if (!isset($filters[$part])) {
                        $filters = false;
                        break;
                    }
                    $filters = $filters[$part];
                }
                if ($filters === false) {
                    user_error('No filters defined for ' . implode('/', $loc));
                    return false;
                }
                return $this->_encode_der($source, $filters + $mapping, null, $special);
            case self::TYPE_NULL:
                $value = '';
                break;
            case self::TYPE_NUMERIC_STRING:
            case self::TYPE_TELETEX_STRING:
            case self::TYPE_PRINTABLE_STRING:
            case self::TYPE_UNIVERSAL_STRING:
            case self::TYPE_UTF8_STRING:
            case self::TYPE_BMP_STRING:
            case self::TYPE_IA5_STRING:
            case self::TYPE_VISIBLE_STRING:
            case self::TYPE_VIDEOTEX_STRING:
            case self::TYPE_GRAPHIC_STRING:
            case self::TYPE_GENERAL_STRING:
                $value = $source;
                break;
            case self::TYPE_BOOLEAN:
                $value = $source ? "\xFF" : "\x00";
                break;
            default:
                user_error('Mapping provides no type definition for ' . implode('/', $this->location));
                return false;
        }
        if (isset($idx)) {
            array_pop($this->location);
        }
        if (isset($mapping['cast'])) {
            if (isset($mapping['explicit']) || $mapping['type'] == self::TYPE_CHOICE) {
                $value = chr($tag) . $this->_encodeLength(strlen($value)) . $value;
                $tag = ($mapping['class'] << 6) | 0x20 | $mapping['cast'];
            } else {
                $tag = ($mapping['class'] << 6) | (ord($temp[0]) & 0x20) | $mapping['cast'];
            }
        }
        return chr($tag) . $this->_encodeLength(strlen($value)) . $value;
    }

    function _encodeLength($length)
    {
        if ($length <= 0x7F) {
            return chr($length);
        }
        $temp = ltrim(pack('N', $length), chr(0));
        return pack('Ca*', 0x80 | strlen($temp), $temp);
    }

    function setTimeFormat($format)
    {
        $this->format = $format;
    }

    function loadOIDs($oids)
    {
        $this->oids = $oids;
    }

    function loadFilters($filters)
    {
        $this->filters = $filters;
    }

    function _string_shift(&$string, $index = 1)
    {
        $substr = substr($string, 0, $index);
        $string = substr($string, $index);
        return $substr;
    }

    function convert($in, $from = self::TYPE_UTF8_STRING, $to = self::TYPE_UTF8_STRING)
    {
        if (!isset($this->stringTypeSize[$from]) || !isset($this->stringTypeSize[$to])) {
            return false;
        }
        $insize = $this->stringTypeSize[$from];
        $outsize = $this->stringTypeSize[$to];
        $inlength = strlen($in);
        $out = '';
        for ($i = 0; $i < $inlength;) {
            if ($inlength - $i < $insize) {
                return false;
            }
            $c = ord($in[$i++]);
            switch (true) {
                case $insize == 4:
                    $c = ($c << 8) | ord($in[$i++]);
                    $c = ($c << 8) | ord($in[$i++]);
                case $insize == 2:
                    $c = ($c << 8) | ord($in[$i++]);
                case $insize == 1:
                    break;
                case ($c & 0x80) == 0x00:
                    break;
                case ($c & 0x40) == 0x00:
                    return false;
                default:
                    $bit = 6;
                    do {
                        if ($bit > 25 || $i >= $inlength || (ord($in[$i]) & 0xC0) != 0x80) {
                            return false;
                        }
                        $c = ($c << 6) | (ord($in[$i++]) & 0x3F);
                        $bit += 5;
                        $mask = 1 << $bit;
                    } while ($c & $bit);
                    $c &= $mask - 1;
                    break;
            }
            $v = '';
            switch (true) {
                case $outsize == 4:
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                case $outsize == 2:
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                case $outsize == 1:
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                    if ($c) {
                        return false;
                    }
                    break;
                case ($c & 0x80000000) != 0:
                    return false;
                case $c >= 0x04000000:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x04000000;
                case $c >= 0x00200000:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x00200000;
                case $c >= 0x00010000:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x00010000;
                case $c >= 0x00000800:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x00000800;
                case $c >= 0x00000080:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x000000C0;
                default:
                    $v .= chr($c);
                    break;
            }
            $out .= strrev($v);
        }
        return $out;
    }
}
