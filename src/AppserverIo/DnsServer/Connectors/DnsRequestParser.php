<?php

/**
 * AppserverIo\DnsServer\Connectors\DnsRequestDecoder
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
 * @link      https://www.appserver.io
 */

namespace AppserverIo\DnsServer\Connectors;

use AppserverIo\DnsServer\Utils\RecordTypeEnum;
use AppserverIo\DnsServer\Interfaces\DnsRequestInterface;
use AppserverIo\DnsServer\Interfaces\DnsResponseInterface;
use AppserverIo\DnsServer\Interfaces\DnsRequestParserInterface;

/**
 * Implementation of a decoder for a DNS request.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      https://www.appserver.io
 */
class DnsRequestParser implements DnsRequestParserInterface
{
    protected $DS_TTL = 300;

    /**
     * Holds the request instance to prepare
     *
     * @var \AppserverIo\DnsServer\Interfaces\DnsRequestInterface
     */
    protected $request;

    /**
     * Holds the response instance to prepare
     *
     * @var \AppserverIo\DnsServer\Interfaces\DnsResponseInterface
     */
    protected $response;

    /**
     * Set's the given request and response class names
     *
     * @param \AppserverIo\DnsServer\Interfaces\DnsRequestInterface  $request  The request instance
     * @param \AppserverIo\DnsServer\Interfaces\DnsResponseInterface $response The response instance
     */
    public function __construct(DnsRequestInterface $request, DnsResponseInterface $response)
    {
        // add request and response
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Will init the request parser anew so it can be reused even when being persistent
     *
     * @return void
     */
    public function init()
    {

        // init request and response
        $this->getRequest()->init();
        $this->getResponse()->init();
    }

    /**
     * Return's the request instance to pass parsed content to
     *
     * @return \AppserverIo\DnsServer\Interfaces\DnsRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Return's the response instance
     *
     * @return \AppserverIo\DnsServer\Interfaces\DnsResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Parses the request data from the passed buffer.
     *
     * @param string $buffer The buffer to decode the data from
     *
     * @return void
     */
    public function parse($buffer)
    {

        $request = $this->getRequest();

        $request->setRawData($rawData = unpack('npacket_id/nflags/nqdcount/nancount/nnscount/narcount', $buffer));
        $offset = $request->getOffset();

        $request->setFlags($this->decodeFlags($rawData['flags']));
        $request->setQuestion($this->ds_decode_question_rr($buffer, $offset, $rawData['qdcount']));
        $request->setAnswer($this->ds_decode_rr($buffer, $offset, $rawData['ancount']));
        $request->setAuthority($this->ds_decode_rr($buffer, $offset, $rawData['nscount']));
        $request->setAdditional($this->ds_decode_rr($buffer, $offset, $rawData['arcount']));
    }

    protected function decodeFlags($flags)
    {
        $res = array();

        $res['qr'] = $flags>>15 &0x1;
        $res['opcode'] = $flags>>11 &0xf;
        $res['aa'] = $flags>>10 &0x1;
        $res['tc'] = $flags>>9 &0x1;
        $res['rd'] = $flags>>8 &0x1;
        $res['ra'] = $flags>>7 &0x1;
        $res['z'] = $flags>>4 &0x7;
        $res['rcode'] = $flags &0xf;

        return $res;
    }

    protected function ds_decode_question_rr($pkt, &$offset, $count)
    {
        $res = array();

        for($i = 0; $i < $count; ++$i) {
            if($offset > strlen($pkt))
                return false;
                $qname = $this->ds_decode_label($pkt, $offset);
                $tmp = unpack('nqtype/nqclass', substr($pkt, $offset, 4));
                $offset += 4;
                $tmp['qname'] = $qname;
                $res[] = $tmp;
        }
        return $res;
    }

    protected function ds_decode_label($pkt, &$offset)
    {
        $end_offset = NULL;
        $qname = '';

        while (1) {
            $len = ord($pkt[$offset]);
            $type = $len>>6 &0x2;

            if ($type) {
                switch ($type) {
                    case 0x2:
                        $new_offset = unpack('noffset', substr($pkt, $offset, 2));
                        $end_offset = $offset +2;
                        $offset = $new_offset['offset'] &0x3fff;
                        break;
                    case 0x1:
                        break;
                }
                continue;
            }

            if ($len > (strlen($pkt) -$offset))
                return NULL;

                if($len == 0) {
                    if($qname == '')
                        $qname = '.';
                        ++$offset;
                        break;
                }
                $qname .= substr($pkt, $offset +1, $len) . '.';
                $offset += $len +1;
        }

        if (!is_null($end_offset)) {
            $offset = $end_offset;
        }

        return $qname;
    }

    protected function ds_decode_rr($pkt, &$offset, $count)
    {
        $res = array();

        for($i = 0; $i < $count; ++$i) {
            // read qname
            $qname = $this->ds_decode_label($pkt, $offset);
            // read qtype & qclass
            $tmp = unpack('ntype/nclass/Nttl/ndlength', substr($pkt, $offset, 10));
            $tmp['name'] = $qname;
            $offset += 10;
            $tmp['data'] = $this->ds_decode_type($tmp['type'], substr($pkt, $offset, $tmp['dlength']));
            $offset += $tmp['dlength'];
            $res[] = $tmp;
        }

        return $res;
    }

    protected function ds_decode_type($type, $val)
    {
        $data = array();

        switch($type) {
            case RecordTypeEnum::TYPE_A:
                $data['value'] = inet_ntop($val);
                break;
            case RecordTypeEnum::TYPE_AAAA:
                $data['value'] = inet_ntop($val);
                break;
            case RecordTypeEnum::TYPE_NS:
                $foo_offset = 0;
                $data['value'] = $this->ds_decode_label($val, $foo_offset);
                break;
            case RecordTypeEnum::TYPE_CNAME:
                $foo_offset = 0;
                $data['value'] = $this->ds_decode_label($val, $foo_offset);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $data['value'] = array();
                $offset = 0;
                $data['value']['mname'] = $this->ds_decode_label($val, $offset);
                $data['value']['rname'] = $this->ds_decode_label($val, $offset);
                $next_values = unpack('Nserial/Nrefresh/Nretry/Nexpire/Nminimum', substr($val, $offset));

                foreach($next_values as $var => $val) {
                    $data['value'][$var] = $val;
                }

                break;
            case RecordTypeEnum::TYPE_PTR:
                $foo_offset = 0;
                $data['value'] = $this->ds_decode_label($val, $foo_offset);
                break;
            case RecordTypeEnum::TYPE_MX:
                $tmp = unpack('n', $val);
                $data['value'] = array('priority' => $tmp[0], 'host' => substr($val, 2), );
                break;
            case RecordTypeEnum::TYPE_TXT:
                $len = ord($val[0]);

                if((strlen($val) +1) < $len) {
                    $data['value'] = NULL;
                    break;
                }

                $data['value'] = substr($val, 1, $len);
                break;
            case RecordTypeEnum::TYPE_AXFR:
                $data['value'] = NULL;
                break;
            case RecordTypeEnum::TYPE_ANY:
                $data['value'] = NULL;
                break;
            case RecordTypeEnum::TYPE_OPT:
                $data['type'] = RecordTypeEnum::TYPE_OPT;
                $data['value'] = array('type' => RecordTypeEnum::TYPE_OPT, 'ext_code' => $this->DS_TTL>>24 &0xff, 'udp_payload_size' => 4096, 'version' => $this->DS_TTL>>16 &0xff, 'flags' => $this->decodeFlags($this->DS_TTL &0xffff));
                break;
            default:
                $data['value'] = $val;
                return false;
        }

        return $data;
    }
}
