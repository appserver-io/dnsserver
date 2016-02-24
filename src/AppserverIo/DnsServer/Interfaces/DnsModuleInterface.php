<?php

/**
 * \AppserverIo\DnsServer\Interfaces\DnsModuleInterface
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
 * @link      http://www.appserver.io
 */

namespace AppserverIo\DnsServer\Interfaces;

use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\ModuleInterface;
use AppserverIo\Server\Interfaces\RequestContextInterface;

/**
 * Interface HttpModuleInterface
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      http://www.appserver.io
 */
interface DnsModuleInterface extends ModuleInterface
{

    /**
     * Implements module logic for given hook.
     *
     * @param \AppserverIo\DnsServer\Interfaces\DnsRequestInterface  $request        A request object
     * @param \AppserverIo\DnsServer\Interfaces\DnsResponseInterface $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function process(DnsRequestInterface $request, DnsResponseInterface $response, RequestContextInterface $requestContext);
}