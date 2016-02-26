<?php

/**
 * AppserverIo\DnsServer\Interfaces\ResolverFactoryInterface
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

namespace AppserverIo\DnsServer\Interfaces;

use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Interfaces\ModuleConfigurationInterface;

/**
 * The interface for all resolver factory implemenations.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      http://www.appserver.io/
 */
interface ResolverFactoryInterface
{

    /**
     * Factory method to create a new DNS resolver instance.
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface       $serverContext       The server context for the resolver
     * @param \AppserverIo\Server\Interfaces\ModuleConfigurationInterface $moduleConfiguration The module configuration with the initialization parameters
     *
     * @return \AppserverIo\DnsServer\StorageProvider\StorageProviderInterface The initialized DNS resolver
     */
    public static function factory(ServerContextInterface $serverContext, ModuleConfigurationInterface $moduleConfiguration);
}
