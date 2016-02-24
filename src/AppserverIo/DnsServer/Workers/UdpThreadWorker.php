<?php

/**
 * \AppserverIo\DnsServer\Workers\UdpThreadWorker
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/server
 * @link      http://www.appserver.io
 */

namespace AppserverIo\DnsServer\Workers;

use AppserverIo\Server\Workers\ThreadWorker;
use AppserverIo\Server\Exceptions\ModuleNotFoundException;
use AppserverIo\Server\Exceptions\ConnectionHandlerNotFoundException;

/**
 * Class ThreadWorker
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/server
 * @link      http://www.appserver.io
 */
class UdpThreadWorker extends ThreadWorker
{

    /**
     * Implements the workers actual logic
     *
     * @return void
     *
     * @throws \AppserverIo\Server\Exceptions\ModuleNotFoundException
     * @throws \AppserverIo\Server\Exceptions\ConnectionHandlerNotFoundException
     */
    public function work()
    {
        // get server context
        $serverContext = $this->getServerContext();
        // get connection handlers
        $connectionHandlers = $this->getConnectionHandlers();

        // set should restart initial flag
        $this->shouldRestart = false;

        try {
            // get socket type
            $socketType = $serverContext->getServerConfig()->getSocketType();

            /** @var SocketInterface $socketType */
            // build connection instance by resource
            $serverConnection = $socketType::getInstance($this->serverConnectionResource);

            // init connection count
            $connectionCount = 0;
            $connectionLimit = rand($this->getAcceptMin(), $this->getAcceptMax());

            // while worker not reached connection limit accept connections and process
            while (++$connectionCount <= $connectionLimit) {
                // accept connections and process working connection by handlers
                if ($serverConnection->receiveFrom(512, STREAM_PEEK)) {
                    // iterate all connection handlers to handle connection right
                    foreach ($connectionHandlers as $connectionHandler) {
                        // if connectionHandler handled connection than break out of foreach
                        if ($connectionHandler->handle($serverConnection, $this)) {
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // log error
            // $serverContext->getLogger()->error($e->__toString());
        }

        // call internal shutdown
        $this->shutdown();
    }
}
