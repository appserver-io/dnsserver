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
 * DNS request parser interface.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      https://www.appserver.io
 */
interface DnsRequestParserInterface
{

    /**
     * Parses the request data from the passed buffer.
     *
     * @param string $buffer The buffer to decode the data from
     *
     * @return void
     */
    public function parse($buffer);

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
