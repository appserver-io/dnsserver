<?php

/**
 * \AppserverIo\DnsServer\Modules\CoreModule
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
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\WebServer\Interfaces\HttpModuleInterface;
use AppserverIo\Psr\HttpMessage\Protocol;
use AppserverIo\Http\HttpResponseStates;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\ModuleVars;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Dictionaries\MimeTypes;
use AppserverIo\WebServer\Interfaces\DnsModuleInterface;

/**
 * Class CoreModule
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class CoreModule implements DnsModuleInterface
{

    /**
     * Defines the module name.
     *
     * @var string MODULE_NAME
     */
    const MODULE_NAME = 'core';

    /**
     * Holds the server context instance
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext
     */
    protected $serverContext;

    /**
     * Returns an array of module names which should be executed first
     *
     * @return array The array of module names
     */
    public function getDependencies()
    {
        return array();
    }

    /**
     * Returns the module name
     *
     * @return string The module name
     */
    public function getModuleName()
    {
        return self::MODULE_NAME;
    }

    /**
     * Initiates the module
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {

        $this->serverContext = $serverContext;

        // JSON formatted DNS records file
        $record_file = 'dns_record.json';

        $jsonStorageProvider = new JsonStorageProvider($record_file);

        // Recursive provider acting as a fallback to the JsonStorageProvider
        $recursiveProvider = new RecursiveProvider($options);

        $this->stackableResolver = new StackableResolver(array($jsonStorageProvider, $recursiveProvider));
    }

    public function getStackableResolver()
    {
        return $this->stackableResolver;
    }

    /**
     * Return's the server context instance
     *
     * @return \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Prepares the module for upcoming request in specific context
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function prepare()
    {
        // nothing to prepare for this module
    }

    /**
     * Implements module logic for given hook
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface          $request        A request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function process(RequestInterface $request, ResponseInterface $response, RequestContextInterface $requestContext)
    {
        $response->appendBodyStream($this->handleQuery($request->getBodyContent());
    }

    private function handleQuery($buffer)
    {

        $data = unpack('npacket_id/nflags/nqdcount/nancount/nnscount/narcount', $buffer);
        $flags = $this->decodeFlags($data['flags']);
        $offset = 12;

        $question = $this->ds_decode_question_rr($buffer, $offset, $data['qdcount']);
        $answer = $this->ds_decode_rr($buffer, $offset, $data['ancount']);
        $authority = $this->ds_decode_rr($buffer, $offset, $data['nscount']);
        $additional = $this->ds_decode_rr($buffer, $offset, $data['arcount']);
        $answer = $this->getStackableResolver()->get_answer($question);
        $flags['qr'] = 1;
        $flags['ra'] = 0;

        $qdcount = count($question);
        $ancount = count($answer);
        $nscount = count($authority);
        $arcount = count($additional);

        $response = pack('nnnnnn', $data['packet_id'], $this->ds_encode_flags($flags), $qdcount, $ancount, $nscount, $arcount);
        $response .= ($p = $this->ds_encode_question_rr($question, strlen($response)));
        $response .= ($p = $this->ds_encode_rr($answer, strlen($response)));
        $response .= $this->ds_encode_rr($authority, strlen($response));
        $response .= $this->ds_encode_rr($additional, strlen($response));

        return $response;
    }

    private function ds_encode_flags($flags)
    {
        $val = 0;

        $val |= ($flags['qr'] &0x1)<<15;
        $val |= ($flags['opcode'] &0xf)<<11;
        $val |= ($flags['aa'] &0x1)<<10;
        $val |= ($flags['tc'] &0x1)<<9;
        $val |= ($flags['rd'] &0x1)<<8;
        $val |= ($flags['ra'] &0x1)<<7;
        $val |= ($flags['z'] &0x7)<<4;
        $val |= ($flags['rcode'] &0xf);

        return $val;
    }

    private function ds_encode_label($str, $offset = NULL)
    {
        $res = '';
        $in_offset = 0;

        if($str == '.') {
            return "\0";
        }

        while(1) {
            $pos = strpos($str, '.', $in_offset);

            if($pos === false) {
                return $res . "\0";
            }

            $res .= chr($pos -$in_offset) . substr($str, $in_offset, $pos -$in_offset);
            $offset += ($pos -$in_offset) +1;
            $in_offset = $pos +1;
        }
    }

    private function ds_encode_question_rr($list, $offset)
    {
        $res = '';

        foreach($list as $rr) {
            $lbl = $this->ds_encode_label($rr['qname'], $offset);
            $offset += strlen($lbl) +4;
            $res .= $lbl;
            $res .= pack('nn', $rr['qtype'], $rr['qclass']);
        }

        return $res;
    }

    private function ds_encode_rr($list, $offset)
    {
        $res = '';

        foreach($list as $rr) {
            $lbl = $this->ds_encode_label($rr['name'], $offset);
            $res .= $lbl;
            $offset += strlen($lbl);

            if(!is_array($rr['data'])) {
                return false;
            }

            $offset += 10;
            $data = $this->ds_encode_type($rr['data']['type'], $rr['data']['value'], $offset);

            if(is_array($data)) {
                // overloading written data
                if(!isset($data['type']))
                    $data['type'] = $rr['data']['type'];
                    if(!isset($data['data']))
                        $data['data'] = '';
                        if(!isset($data['class']))
                            $data['class'] = $rr['class'];
                            if(!isset($data['ttl']))
                                $data['ttl'] = $rr['ttl'];
                                $offset += strlen($data['data']);
                                $res .= pack('nnNn', $data['type'], $data['class'], $data['ttl'], strlen($data['data'])) . $data['data'];
            } else {
                $offset += strlen($data);
                $res .= pack('nnNn', $rr['data']['type'], $rr['class'], $rr['ttl'], strlen($data)) . $data;
            }
        }

        return $res;
    }

    private function ds_encode_type($type, $val = NULL, $offset = NULL)
    {
        switch ($type) {
            case RecordTypeEnum::TYPE_A:
                $enc = inet_pton($val);
                if(strlen($enc) != 4)
                    $enc = "\0\0\0\0";
                    return $enc;
            case RecordTypeEnum::TYPE_AAAA:
                $enc = inet_pton($val);
                if(strlen($enc) != 16)
                    $enc = str_repeat("\0", 16);
                    return $enc;
            case RecordTypeEnum::TYPE_NS:
                $val = rtrim($val,'.').'.';
                return $this->ds_encode_label($val, $offset);
            case RecordTypeEnum::TYPE_CNAME:
                $val = rtrim($val,'.').'.';
                return $this->ds_encode_label($val, $offset);
            case RecordTypeEnum::TYPE_SOA:
                $res = '';
                $val['mname'] = rtrim($val['mname'],'.').'.';
                $val['rname'] = rtrim($val['rname'],'.').'.';
                $res .= $this->ds_encode_label($val['mname'], $offset);
                $res .= $this->ds_encode_label($val['rname'], $offset +strlen($res));
                $res .= pack('NNNNN', $val['serial'], $val['refresh'], $val['retry'], $val['expire'], $val['minimum-ttl']);
                return $res;
            case RecordTypeEnum::TYPE_PTR:
                $val = rtrim($val,'.').'.';
                return $this->ds_encode_label($val, $offset);
            case RecordTypeEnum::TYPE_MX:
                $val = rtrim($val,'.').'.';
                return pack('n', 10) . $this->ds_encode_label($val, $offset +2);
            case RecordTypeEnum::TYPE_TXT:
                if(strlen($val) > 255)
                    $val = substr($val, 0, 255);

                    return chr(strlen($val)) . $val;
            case RecordTypeEnum::TYPE_AXFR:
                return '';
            case RecordTypeEnum::TYPE_ANY:
                return '';
            case RecordTypeEnum::TYPE_OPT:
                $res = array('class' => $val['udp_payload_size'], 'ttl' => (($val['ext_code'] &0xff)<<24) |(($val['version'] &0xff)<<16) |($this->ds_encode_flags($val['flags']) &0xffff), 'data' => '',
                // // TODO: encode data
                );

                return $res;
            default:
                return $val;
        }
    }

    public function ds_error($code, $error, $file, $line)
    {
        if(!(error_reporting() &$code)) {
            return;
        }

        $codes = array(E_ERROR => 'Error', E_WARNING => 'Warning', E_PARSE => 'Parse Error', E_NOTICE => 'Notice', E_CORE_ERROR => 'Core Error', E_CORE_WARNING => 'Core Warning', E_COMPILE_ERROR => 'Compile Error', E_COMPILE_WARNING => 'Compile Warning', E_USER_ERROR => 'User Error', E_USER_WARNING => 'User Warning', E_USER_NOTICE => 'User Notice', E_STRICT => 'Strict Notice', E_RECOVERABLE_ERROR => 'Recoverable Error', E_DEPRECATED => 'Deprecated Error', E_USER_DEPRECATED => 'User Deprecated Error');

        $type = isset($codes[$code]) ? $codes[$code] : 'Unknown Error';

        die(sprintf('DNS Server error: [%s] "%s" in file "%s" on line "%d".%s', $type, $error, $file, $line, PHP_EOL));
    }
}
