<?php

/**
 * AppserverIo\DnsServer\StorageProvider\StandardResolverFactory
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

namespace AppserverIo\DnsServer\StorageProvider;

use AppserverIo\DnsServer\StorageProvider\RecursiveProvider;
use AppserverIo\DnsServer\StorageProvider\StackableResolver;
use AppserverIo\DnsServer\Interfaces\ResolverFactoryInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Interfaces\ModuleConfigurationInterface;

/**
 * A simple storage provider factory implementation that creates a JSON provider
 * using the file configured in the module configuration to load the DNS records.
 *
 * Additionally it adds a simple fallback provider that uses another DNS server
 * load the records as fallback.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      http://www.appserver.io/
 */
class StandardResolverFactory implements ResolverFactoryInterface
{

    /**
     * Factory method to create a new DNS resolver instance.
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface       $serverContext       The server context for the resolver
     * @param \AppserverIo\Server\Interfaces\ModuleConfigurationInterface $moduleConfiguration The module configuration with the initialization parameters
     *
     * @return \AppserverIo\DnsServer\StorageProvider\StorageProviderInterface The initialized DNS resolver
     */
    public static function factory(ServerContextInterface $serverContext, ModuleConfigurationInterface $moduleConfiguration)
    {

        // initialize the DNS resolver to load the DNS entries from the storage
        return new StackableResolver(array(new JsonStorageProvider($moduleConfiguration), new RecursiveProvider()));
    }
}
