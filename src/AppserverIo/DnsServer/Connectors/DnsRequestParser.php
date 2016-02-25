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

use AppserverIo\DnsServer\Utils\DnsUtil;
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

        // create a local copy of the requst
        $request = $this->getRequest();

        // set the raw data in the request
        $request->setRawData($rawData = unpack('npacket_id/nflags/nqdcount/nancount/nnscount/narcount', $buffer));

        // load the offset to start extracting the data
        $offset = $request->getOffset();

        // initialize the request with the extracted DNS values
        $request->setFlags(DnsUtil::singleton()->decodeFlags($rawData['flags']));
        $request->setQuestion(DnsUtil::singleton()->decodeQuestionResourceRecord($buffer, $offset, $rawData['qdcount']));
        $request->setAnswer(DnsUtil::singleton()->decodeResourceRecord($buffer, $offset, $rawData['ancount']));
        $request->setAuthority(DnsUtil::singleton()->decodeResourceRecord($buffer, $offset, $rawData['nscount']));
        $request->setAdditional(DnsUtil::singleton()->decodeResourceRecord($buffer, $offset, $rawData['arcount']));
    }
}
