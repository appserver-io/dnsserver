<?php

/**
 * AppserverIo\DnsServer\Interfaces\DnsRequestInterface
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
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      https://www.appserver.io
 */

namespace AppserverIo\DnsServer\Interfaces;

/**
 * Interface for a DNS request.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      https://www.appserver.io
 */
interface DnsRequestInterface
{

    /**
     * Initialises the request object to default properties
     *
     * @return void
     */
    public function init();

    /**
     * Resets the stream resource pointing to body content
     *
     * @param resource $bodyStream The body content stream resource
     *
     * @return void
     */
    public function setBodyStream($bodyStream);

    /**
     * Returns the stream resource pointing to body content
     *
     * @return resource The body content stream resource
     */
    public function getBodyStream();

    /**
     * Returns the body content stored in body stream
     *
     * @return string
     */
    public function getBodyContent();

    /**
     * Copies a source stream to body stream
     *
     * @param resource $sourceStream The file pointer to source stream
     * @param int      $maxlength    The max length to read from source stream
     * @param int      $offset       The offset from source stream to read
     *
     * @return int the total count of bytes copied.
     */
    public function copyBodyStream($sourceStream, $maxlength = null, $offset = null);

    /**
     * Appends body stream with content
     *
     * @param string $content The content to append
     *
     * @return int
     */
    public function appendBodyStream($content);
}
