<?php

/**
 * AppserverIo\DnsServer\Interfaces\DnsRequestParserInterface
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

namespace AppserverIo\DnsServer\Interfaces;

/**
 * Interface HttpRequestParserInterface
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      https://www.appserver.io
 */
interface DnsRequestDecoderInterface
{
    /**
     * Parses the start line
     *
     * @param string $line The start line
     *
     * @return void
     * @throws \AppserverIo\Http\HttpException
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.1
     */
    public function parseStartLine($line);

    /**
     * Parse's the header line to get method, uri and version
     *
     * @param string $line The line defining a http request header
     *
     * @return mixed
     */
    public function parseHeaderLine($line);

    /**
     * Parse headers in a proper way
     *
     * @param string $messageHeaders The message headers
     *
     * @return void
     * @throws \AppserverIo\Http\HttpException
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
     */
    public function parseHeaders($messageHeaders);

    /**
     * Return's the request instance to pass parsed content to
     *
     * @return \AppserverIo\Psr\HttpMessage\RequestInterface
     */
    public function getRequest();
    /**
     * Return's the response instance
     *
     * @return \AppserverIo\Psr\HttpMessage\ResponseInterface
     */
    public function getResponse();
}
