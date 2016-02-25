<?php

/**
 * AppserverIo\DnsServer\Utils\DnsUtil
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\DnsServer\Utils;

/**
 * Library providing functionality to encode/decode DNS requests.
 *
 * As this library has been copied form the project PHP DNS Server we
 * want to thank the authors for their brilliant work!
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      http://www.appserver.io/
 * @link      https://github.com/yswery/PHP-DNS-SERVER
 */
class DnsUtil
{

    /**
     * The TTL in seconds for the domain record.
     *
     * @var integer
     */
    protected $ttl = 300;

    /**
     * The singleton instance.
     *
     * @var \AppserverIo\DnsServer\Utils\DnsUtil
     */
    protected static $singleton;

    /**
     * This is a utility class, so protect it against direct
     * instantiation.
     */
    protected function __construct()
    {
    }

    /**
     * This is a utility class, so protect it against cloning.
     *
     * @return void
     */
    protected function __clone()
    {
    }

    /**
     * Return's the singleton instance.
     *
     * @return \AppserverIo\DnsServer\Utils\DnsUtil The singleton
     */
    public static function singleton()
    {

        // query whether or not we've already initialize an instance
        if (DnsUtil::$singleton == null) {
            DnsUtil::$singleton = new DnsUtil();
        }

        // return the singleton instance
        return DnsUtil::$singleton;
    }

    /**
     * Decode the DNS flags passed with the request.
     *
     * @param array $flags The DNS flags
     *
     * @return array The array with the decoded flags
     */
    public function decodeFlags($flags)
    {

        // initialize the array
        $res = array();

        // decode the flags
        $res['qr'] = $flags>>15 &0x1;
        $res['opcode'] = $flags>>11 &0xf;
        $res['aa'] = $flags>>10 &0x1;
        $res['tc'] = $flags>>9 &0x1;
        $res['rd'] = $flags>>8 &0x1;
        $res['ra'] = $flags>>7 &0x1;
        $res['z'] = $flags>>4 &0x7;
        $res['rcode'] = $flags &0xf;

        // return the flags
        return $res;
    }

    /**
     * Decode the question resource record passed with the request.
     *
     * @param string  $pkt    The buffer to extract the question from
     * @param integer $offset The offset to start decoding from
     * @param unknown $count  The number packages to decode
     *
     * @return array The question resource record
     */
    public function decodeQuestionResourceRecord($pkt, &$offset, $count)
    {

        // initialize the array
        $res = array();

        // iterate over the string to deocde the data
        for ($i = 0; $i < $count; ++$i) {
            // if we've reached the offset, stop processing
            if ($offset > strlen($pkt)) {
                return false;
            }

            // decode the data
            $qname = $this->decodeLabel($pkt, $offset);
            $tmp = unpack('nqtype/nqclass', substr($pkt, $offset, 4));
            $offset += 4;
            $tmp['qname'] = $qname;
            $res[] = $tmp;
        }

        // return the question resource record
        return $res;
    }

    /**
     * Decode the DNS label.
     *
     * @param string  $pkt    The buffer to extract the label from
     * @param integer $offset The offset to start decoding from
     *
     * @return string The decoded label
     */
    public function decodeLabel($pkt, &$offset)
    {

        // initialize offset end and qname
        $endOffset = null;
        $qname = '';

        // loop until the label has been decoded
        while (1) {
            // initialize length and type
            $len = ord($pkt[$offset]);
            $type = $len>>6 &0x2;

            // try to decode the tpye
            if ($type) {
                switch ($type) {
                    case 0x2:
                        $newOffset = unpack('noffset', substr($pkt, $offset, 2));
                        $endOffset = $offset + 2;
                        $offset = $newOffset['offset'] &0x3fff;
                        break;
                    case 0x1:
                        break;
                }
                continue;
            }

            // query whether or not we've already reached the offset
            if ($len > (strlen($pkt) - $offset)) {
                return null;
            }

            // if we've found the end
            if ($len == 0) {
                if ($qname == '') {
                    $qname = '.';
                }
                ++$offset;
                break;
            }

            // append the qname and update the offset
            $qname .= substr($pkt, $offset +1, $len) . '.';
            $offset += $len +1;
        }

        // set the new offset
        if (!is_null($endOffset)) {
            $offset = $endOffset;
        }

        // return the qname
        return $qname;
    }

    /**
     * Decode the resource record.
     *
     * @param string  $pkt    The buffer to extract the question from
     * @param integer $offset The offset to start decoding from
     * @param unknown $count  The number packages to decode
     *
     * @return array The decoded resource record
     */
    public function decodeResourceRecord($pkt, &$offset, $count)
    {

        // initialize the resource record
        $res = array();

        // iterate over the string to deocde the data
        for ($i = 0; $i < $count; ++$i) {
            // read qname
            $qname = $this->decodeLabel($pkt, $offset);
            // read qtype & qclass
            $tmp = unpack('ntype/nclass/Nttl/ndlength', substr($pkt, $offset, 10));
            $tmp['name'] = $qname;
            $offset += 10;
            $tmp['data'] = $this->decodeType($tmp['type'], substr($pkt, $offset, $tmp['dlength']));
            $offset += $tmp['dlength'];
            $res[] = $tmp;
        }

        // return the resource record
        return $res;
    }

    /**
     * Decode the DNS type.
     *
     * @param string $type The requested type
     * @param string $val  The value to decode
     *
     * @return array The decoded type data
     */
    public function decodeType($type, $val)
    {

        // initialize the type data
        $data = array();

        // query the type we've to decode
        switch ($type) {
            case RecordTypeEnum::TYPE_A:
                $data['value'] = inet_ntop($val);
                break;

            case RecordTypeEnum::TYPE_AAAA:
                $data['value'] = inet_ntop($val);
                break;

            case RecordTypeEnum::TYPE_NS:
                $fooOffset = 0;
                $data['value'] = $this->decodeLabel($val, $fooOffset);
                break;

            case RecordTypeEnum::TYPE_CNAME:
                $fooOffset = 0;
                $data['value'] = $this->decodeLabel($val, $fooOffset);
                break;

            case RecordTypeEnum::TYPE_SOA:
                $data['value'] = array();
                $offset = 0;
                $data['value']['mname'] = $this->decodeLabel($val, $offset);
                $data['value']['rname'] = $this->decodeLabel($val, $offset);
                $next_values = unpack('Nserial/Nrefresh/Nretry/Nexpire/Nminimum', substr($val, $offset));
                foreach($next_values as $var => $val) {
                    $data['value'][$var] = $val;
                }
                break;

            case RecordTypeEnum::TYPE_PTR:
                $fooOffset = 0;
                $data['value'] = $this->decodeLabel($val, $fooOffset);
                break;
            case RecordTypeEnum::TYPE_MX:
                $tmp = unpack('n', $val);
                $data['value'] = array('priority' => $tmp[0], 'host' => substr($val, 2), );
                break;

            case RecordTypeEnum::TYPE_TXT:
                $len = ord($val[0]);
                if ((strlen($val) +1) < $len) {
                    $data['value'] = null;
                    break;
                }
                $data['value'] = substr($val, 1, $len);
                break;

            case RecordTypeEnum::TYPE_AXFR:
                $data['value'] = null;
                break;

            case RecordTypeEnum::TYPE_ANY:
                $data['value'] = null;
                break;

            case RecordTypeEnum::TYPE_OPT:
                $data['type'] = RecordTypeEnum::TYPE_OPT;
                $data['value'] = array(
                    'type' => RecordTypeEnum::TYPE_OPT,
                    'ext_code' => $this->ttl>>24 &0xff,
                    'udp_payload_size' => 4096,
                    'version' => $this->ttl>>16 &0xff,
                    'flags' => $this->decodeFlags($this->ttl &0xffff)
                );
                break;

            default:
                $data['value'] = $val;
                return false;
        }

        // return the type data
        return $data;
    }

    /**
     * Encode the passed flags for the DNS response.
     *
     * @param array $flags The flags to encode
     *
     * @return integer The encoded flags
     */
    public function encodeFlags($flags)
    {

        // initialize the encoded flags
        $val = 0;

        // encode the flags
        $val |= ($flags['qr'] &0x1)<<15;
        $val |= ($flags['opcode'] &0xf)<<11;
        $val |= ($flags['aa'] &0x1)<<10;
        $val |= ($flags['tc'] &0x1)<<9;
        $val |= ($flags['rd'] &0x1)<<8;
        $val |= ($flags['ra'] &0x1)<<7;
        $val |= ($flags['z'] &0x7)<<4;
        $val |= ($flags['rcode'] &0xf);

        // return the encoded flags
        return $val;
    }

    /**
     * Encode the passed label for the DNS response.
     *
     * @param string  $str    The label to encode
     * @param integer $offset The offset where to start encoding
     *
     * @return The encoded label
     */
    public function encodeLabel($str, $offset = null)
    {

        // initialize the encoded label and the offset
        $res = '';
        $inOffset = 0;

        // return immediately
        if ($str == '.') {
            return "\0";
        }

        // loop while we encode the label
        while (1) {
            // load the position of the .
            $pos = strpos($str, '.', $inOffset);
            // if we can't find it, return immediately
            if ($pos === false) {
                return $res . "\0";
            }
            // encode the  label
            $res .= chr($pos -$inOffset) . substr($str, $inOffset, $pos -$inOffset);
            $offset += ($pos -$inOffset) +1;
            $inOffset = $pos +1;
        }
    }

    /**
     * Encode the passed question resource record for the DNS response.
     *
     * @param array   $list   The question resource recored to encode
     * @param integer $offset The offset where to start encoding
     *
     * @return string The encoded question resource record
     */
    public function encodeQuestionResourceRecord($list, $offset)
    {

        // initialize the question resource record
        $res = '';

        // iterate over the passed list and encode the values
        foreach ($list as $rr) {
            $lbl = $this->encodeLabel($rr['qname'], $offset);
            $offset += strlen($lbl) +4;
            $res .= $lbl;
            $res .= pack('nn', $rr['qtype'], $rr['qclass']);
        }

        // return the question resource record
        return $res;
    }

    /**
     * Encode the passed resource record for the DNS response.
     *
     * @param array   $list   The resource recored to encode
     * @param integer $offset The offset where to start encoding
     *
     * @return string The encoded resource record
     */
    public function encodeResourceRecord($list, $offset)
    {

        // initialize the resource record
        $res = '';

        // iterate over the passed list and encode the values
        foreach ($list as $rr) {
            // encode the label
            $lbl = $this->encodeLabel($rr['name'], $offset);
            $res .= $lbl;
            $offset += strlen($lbl);

            // return immediately if we can't find any data
            if (!is_array($rr['data'])) {
                return false;
            }

            // raise the offset and encode the type
            $offset += 10;
            $data = $this->encodeType($rr['data']['type'], $rr['data']['value'], $offset);

            // encode the data if available
            if (is_array($data)) {
                // overloading written data
                if (!isset($data['type'])) {
                    $data['type'] = $rr['data']['type'];
                    if (!isset($data['data'])) {
                        $data['data'] = '';
                        if (!isset($data['class'])) {
                            $data['class'] = $rr['class'];
                            if (!isset($data['ttl'])) {
                                $data['ttl'] = $rr['ttl'];
                                $offset += strlen($data['data']);
                                $res .= pack('nnNn', $data['type'], $data['class'], $data['ttl'], strlen($data['data'])) . $data['data'];
                            }
                        }
                    }
                }

            } else {
                $offset += strlen($data);
                $res .= pack('nnNn', $rr['data']['type'], $rr['class'], $rr['ttl'], strlen($data)) . $data;
            }
        }

        // return the encoded resource record
        return $res;
    }

    /**
     * Encode the type for the DNS response.
     *
     * @param string  $type   The type to encode
     * @param string  $val    The value to encode
     * @param integer $offset The offset where to start encoding
     *
     * @return mixed The encoded type
     */
    public function encodeType($type, $val = null, $offset = null)
    {

        // query the type we've to encode
        switch ($type) {
            case RecordTypeEnum::TYPE_A:
                $enc = inet_pton($val);
                if (strlen($enc) != 4) {
                    $enc = "\0\0\0\0";
                }
                return $enc;

            case RecordTypeEnum::TYPE_AAAA:
                $enc = inet_pton($val);
                if (strlen($enc) != 16) {
                    $enc = str_repeat("\0", 16);
                }
                return $enc;

            case RecordTypeEnum::TYPE_NS:
                $val = rtrim($val,'.').'.';
                return $this->encodeLabel($val, $offset);

            case RecordTypeEnum::TYPE_CNAME:
                $val = rtrim($val,'.').'.';
                return $this->encodeLabel($val, $offset);

            case RecordTypeEnum::TYPE_SOA:
                $res = '';
                $val['mname'] = rtrim($val['mname'],'.').'.';
                $val['rname'] = rtrim($val['rname'],'.').'.';
                $res .= $this->encodeLabel($val['mname'], $offset);
                $res .= $this->encodeLabel($val['rname'], $offset +strlen($res));
                $res .= pack('NNNNN', $val['serial'], $val['refresh'], $val['retry'], $val['expire'], $val['minimum-ttl']);
                return $res;

            case RecordTypeEnum::TYPE_PTR:
                $val = rtrim($val,'.').'.';
                return $this->encodeLabel($val, $offset);

            case RecordTypeEnum::TYPE_MX:
                $val = rtrim($val,'.').'.';
                return pack('n', 10) . $this->encodeLabel($val, $offset +2);

            case RecordTypeEnum::TYPE_TXT:
                if(strlen($val) > 255) {
                    $val = substr($val, 0, 255);
                }
                return chr(strlen($val)) . $val;

            case RecordTypeEnum::TYPE_AXFR:
                return '';

            case RecordTypeEnum::TYPE_ANY:
                return '';

            case RecordTypeEnum::TYPE_OPT:
                $res = array(
                'class' => $val['udp_payload_size'],
                'ttl' => (($val['ext_code'] &0xff)<<24) | (($val['version'] &0xff)<<16) | ($this->encodeFlags($val['flags']) &0xffff),
                'data' => '',
                // // TODO: encode data
                );
                return $res;

            default:
                return $val;
        }
    }
}
