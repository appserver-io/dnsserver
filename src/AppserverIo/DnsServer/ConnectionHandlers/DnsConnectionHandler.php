<?php

/**
 * \AppserverIo\DnsServer\ConnectionHandlers\DnsConnectionHandler
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

namespace AppserverIo\DnsServer\ConnectionHandlers;

use AppserverIo\Psr\Socket\SocketInterface;
use AppserverIo\DnsServer\Connectors\DnsRequest;
use AppserverIo\DnsServer\Connectors\DnsResponse;
use AppserverIo\DnsServer\Connectors\DnsRequestParser;
use AppserverIo\Server\Interfaces\WorkerInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Interfaces\ConnectionHandlerInterface;

/**
 * A handler implementation for DNS requests.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      https://www.appserver.io
 */
class DnsConnectionHandler implements ConnectionHandlerInterface
{

    /**
     * Defines the read length for DNS connections.
     *
     * @var integer
     */
    const DNS_CONNECTION_READ_LENGTH = 512;

    /**
     * Holds parser instance
     *
     * @var \AppserverIo\DnsServer\Interfaces\DnsRequestParserInterface
     */
    protected $parser;

    /**
     * Holds the server context instance
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Holds the request's context instance
     *
     * @var \AppserverIo\Server\Interfaces\RequestContextInterface
     */
    protected $requestContext;


    /**
     * Holds the connection instance
     *
     * @var \AppserverIo\Psr\Socket\SocketInterface
     */
    protected $connection;

    /**
     * Holds the worker instance
     *
     * @var \AppserverIo\Server\Interfaces\WorkerInterface
     */
    protected $worker;

    /**
     * Flag if a shutdown function was registered or not
     *
     * @var boolean
     */
    protected $hasRegisteredShutdown = false;

    /**
     * Holds an array of modules to use for connection handler
     *
     * @var array
     */
    protected $modules;

    /**
     * Inits the connection handler by given context and params
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context
     * @param array                                                 $params        The params for connection handler
     *
     * @return void
     */
    public function init(ServerContextInterface $serverContext, array $params = null)
    {

        $this->serverContext = $serverContext;

        // init DNS request object
        $dnsRequest = new DnsRequest();

        // init DNS response object
        $dnsResponse = new DnsResponse();

        // setup DNS parser
        $this->parser = new DnsRequestParser($dnsRequest, $dnsResponse);

        // get request context type
        $requestContextType = $this->getServerConfig()->getRequestContextType();

        /**
         * @var \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext
         */
        // instantiate and init request context
        $this->requestContext = new $requestContextType();
        $this->requestContext->init($this->getServerConfig());
    }

    /**
     * Injects all needed modules for connection handler to process
     *
     * @param array $modules An array of Modules
     *
     * @return void
     */
    public function injectModules($modules)
    {
        $this->modules = $modules;
    }

    /**
     * Returns all needed modules as array for connection handler to process
     *
     * @return array An array of Modules
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Returns a specific module instance by given name
     *
     * @param string $name The modules name to return an instance for
     *
     * @return \AppserverIo\WebServer\Interfaces\HttpModuleInterface|null
     */
    public function getModule($name)
    {
        if (isset($this->modules[$name])) {
            return $this->modules[$name];
        }
    }

    /**
     * Returns the DNS parser instance.
     *
     * @return \AppserverIo\DnsServer\Interfaces\DnsRequestParserInterface
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Returns the request's context instance
     *
     * @return \AppserverIo\Server\Interfaces\RequestContextInterface
     */
    public function getRequestContext()
    {
        return $this->requestContext;
    }
    /**
     * Returns the server context instance
     *
     * @return \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Returns the server's configuration
     *
     * @return \AppserverIo\Server\Interfaces\ServerConfigurationInterface
     */
    public function getServerConfig()
    {
        return $this->getServerContext()->getServerConfig();
    }

    /**
     * Returns the connection used to handle with
     *
     * @return \AppserverIo\Psr\Socket\SocketInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Returns the worker instance which starte this worker thread
     *
     * @return \AppserverIo\Server\Interfaces\WorkerInterface
     */
    protected function getWorker()
    {
        return $this->worker;
    }

    /**
     * Handles the connection with the connected client in a proper way the given
     * protocol type and version expects for example.
     *
     * @param \AppserverIo\Psr\Socket\SocketInterface        $connection The connection to handle
     * @param \AppserverIo\Server\Interfaces\WorkerInterface $worker     The worker how started this handle
     *
     * @return bool Weather it was responsible to handle the firstLine or not.
     * @throws \Exception
     */
    public function handle(SocketInterface $connection, WorkerInterface $worker)
    {
        try {

            // register shutdown handler once to avoid strange memory consumption problems
            $this->registerShutdown();

            // add connection ref to self
            $this->connection = $connection;
            $this->worker = $worker;

            $parser = $this->getParser();

            // init the request parser
            $parser->init();

            // get local var refs
            $connection = $this->getConnection();

            $this->getParser()->parse($connection->receiveFrom());

            $this->processModules();

            $this->sendResponse();

        } catch (\Exception $e) {
            $this->getServerContext()->getLogger()->error($e->__toString());
        }
    }
    /**
     * Sends response to connected client
     *
     * @return void
     */
    public function sendResponse()
    {
        // get local var refs
        $response = $this->getParser()->getResponse();
        $inputStream = $response->getBodyStream();
        $connection = $this->getConnection();
        // try to rewind stream
        @rewind($inputStream);
        // stream response to client connection
        while ($readContent = fread($inputStream, 4096)) {
            $connection->sendTo($readContent);
        }
    }

    /**
     * Process the modules logic.
     *
     * @return void
     */
    protected function processModules()
    {

        // get object refs to local vars
        $requestContext = $this->getRequestContext();
        $request = $this->getParser()->getRequest();
        $response = $this->getParser()->getResponse();

        // interate all modules and call process by given hook
        foreach ($this->getModules() as $module) {
            // process modules logic by hook
            /** @var $module \AppserverIo\DnsServer\Interfaces\DnsModuleInterface */
            $module->process($request, $response, $requestContext);
        }
    }

    /**
     * Registers the shutdown function in this context
     *
     * @return void
     */
    public function registerShutdown()
    {
        // register shutdown handler once to avoid strange memory consumption problems
        if ($this->hasRegisteredShutdown === false) {
            register_shutdown_function(array( &$this, "shutdown"));
            $this->hasRegisteredShutdown = true;
        }
    }

    /**
     * Does shutdown logic for worker if something breaks in process
     *
     * @return void
     */
    public function shutdown()
    {

        // check if connections is still alive
        if ($connection = $this->getConnection()) {
            $connection->close();
        }

        // check if worker is given
        if ($worker = $this->getWorker()) {
            $worker->shutdown();
        }
    }
}
