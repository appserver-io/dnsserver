<?php

/**
 * AppserverIo\DnsServer\Modules\CoreModule
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

namespace AppserverIo\DnsServer\Modules;

use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\DnsServer\Interfaces\DnsModuleInterface;
use AppserverIo\DnsServer\Interfaces\DnsRequestInterface;
use AppserverIo\DnsServer\Interfaces\DnsResponseInterface;
use AppserverIo\DnsServer\StorageProvider\JsonStorageProvider;
use AppserverIo\DnsServer\StorageProvider\RecursiveProvider;
use AppserverIo\DnsServer\StorageProvider\StackableResolver;
use AppserverIo\DnsServer\Utils\DnsUtil;

/**
 * Class CoreModule
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
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

        // set the server context
        $this->serverContext = $serverContext;

        // JSON formatted DNS records file
        $record_file = 'etc/dns_record.json';

        // initialize the storage provider
        $jsonStorageProvider = new JsonStorageProvider($record_file);

        // Recursive provider acting as a fallback to the JsonStorageProvider
        $recursiveProvider = new RecursiveProvider();

        // initialize the DNS resolver to load the DNS entries from the storage
        $this->stackableResolver = new StackableResolver(array($jsonStorageProvider, $recursiveProvider));
    }

    /**
     * The resolver to load the DNS entries with.
     *
     * @return \AppserverIo\DnsServer\StorageProvider\StackableResolver The resolver instance
     */
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
     * Implements module logic.
     *
     * @param \AppserverIo\DnsServer\Interfaces\DnsRequestInterface  $request        A request object
     * @param \AppserverIo\DnsServer\Interfaces\DnsResponseInterface $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     *
     * @return void
     */
    public function process(DnsRequestInterface $request, DnsResponseInterface $response, RequestContextInterface $requestContext)
    {

        // load the answer from our DNS database
        $answer = $this->getStackableResolver()->getAnswer($question = $request->getQuestion());

        // merge the flags
        $flags = array_merge($request->getFlags(), array('qr' => 1, 'ra' => 0));

        // prepare the numbers for the encoding
        $ancount = count($answer);
        $qdcount = count($question);
        $nscount = count($authority = $request->getAuthority());
        $arcount = count($additional = $request->getAdditional());

        // prepare the encoded DNS response
        $res = pack('nnnnnn', $request->getData('packet_id'), DnsUtil::singleton()->encodeFlags($flags), $qdcount, $ancount, $nscount, $arcount);
        $res .= DnsUtil::singleton()->encodeQuestionResourceRecord($question, strlen($res));
        $res .= DnsUtil::singleton()->encodeResourceRecord($answer, strlen($res));
        $res .= DnsUtil::singleton()->encodeResourceRecord($authority, strlen($res));
        $res .= DnsUtil::singleton()->encodeResourceRecord($additional, strlen($res));

        // append the response to the body stream
        $response->appendBodyStream($res);
    }
}
