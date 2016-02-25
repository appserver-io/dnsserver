<?php

/**
 * AppserverIo\DnsServer\StorageProvider\AbstractStorageProvider
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

/**
 * A wrapper for multiple resolvers.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      http://www.appserver.io/
 */
class StackableResolver
{

    /**
     * The available DNS resolvers.
     *
     * @var array
     */
    protected $resolvers;

    /**
     * Initializes the wrapper with the available resolvers.
     *
     * @param array $resolvers The resolvers
     */
    public function __construct(array $resolvers = array())
    {
        $this->resolvers = $resolvers;
    }

    /**
     * Return's the available resolvers.
     *
     * @return array The available resolvers
     */
    protected function getResolvers()
    {
        return $this->resolvers;
    }

    /**
     * Return's the answer to the passed question to resolve a DNS request.
     *
     * @param array $question The question
     *
     * @return array The answer
     */
    public function getAnswer(array $question)
    {

        // iterate over the resolvers and try to find an answer to the passed question
        foreach ($this->getResolvers() as $resolver) {
            $answer = $resolver->getAnswer($question);
            // query whether or not, the resolver has the answer
            if ($answer) {
                return $answer;
            }
        }

        // return an empty array, because we don't have an answer
        return array();
    }
}
