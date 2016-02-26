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
 * @link      http://www.php.net/dns_get_record
 */

namespace AppserverIo\DnsServer\StorageProvider;

use AppserverIo\DnsServer\Utils\RecordTypeEnum;

/**
 * A provider that uses the PHP function dns_get_record() to recursively
 * load the records for a DNS request.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      http://www.appserver.io/
 */
class RecursiveProvider extends AbstractStorageProvider
{

    /**
     * The DNS answer names.
     *
     * @var array
     */
    protected $dnsAnswerNames = array(
        'DNS_A' => 'ip',
        'DNS_AAAA' => 'ipv6',
        'DNS_CNAME' => 'target',
        'DNS_TXT' => 'txt',
        'DNS_MX' => 'target',
        'DNS_NS' => 'target',
        'DNS_SOA' => array('mname', 'rname', 'serial', 'retry', 'refresh', 'expire', 'minimum-ttl'),
        'DNS_PTR' => 'target',
    );

    /**
     * Return's the answer to the passed question to resolve a DNS request.
     *
     * @param array $question The question
     *
     * @return array The answer
     */
    public function getAnswer(array $question)
    {

        // initialize the variables
        $answer = array();
        $domain = trim($question[0]['qname'], '.');
        $type = RecordTypeEnum::getName($question[0]['qtype']);

        // load the matching DNS records
        $records = $this->getRecordsRecursivly($domain, $type);

        // prepare the answer for each record we found
        foreach ($records as $record) {
            $answer[] = array(
                'name' => $question[0]['qname'],
                'class' => $question[0]['qclass'],
                'ttl' => $record['ttl'],
                'data' => array('type' => $question[0]['qtype'], 'value' => $record['answer'])
            );
        }

        // return the answer
        return $answer;
    }

    /**
     * Tries to load the DNS records for the passed domain and type recursively
     * by using the PHP dns_get_record() function.
     *
     * @param string $domain The domain to load the DNS record for
     * @param string $type   The type to load the DNS recored for
     *
     * @return array The DNS recored
     * @throws \Exception Is thrown if the passed is not supported
     */
    protected function getRecordsRecursivly($domain, $type)
    {

        // prepare the result nd the constant name
        $result = array();
        $dnsConstName =  $this->getDnsConstName($type);

        // query whether or not the type is supported
        if (!$dnsConstName) {
            throw new \Exception('Not supported dns type to query.');
        }

        // load the answer name and the available DNS records
        $dnsAnswerName = $this->dnsAnswerNames[$dnsConstName];
        $records = dns_get_record($domain, constant($dnsConstName));

        // declare the array for the answers
        $answer = array();

        // prepare the answer
        foreach ($records as $record) {
            if (is_array($dnsAnswerName)) {
                foreach ($dnsAnswerName as $name) {
                    $answer[$name] = $record[$name];
                }

            } else {
                $answer = $record[$dnsAnswerName];
            }

            // append the answer to the result
            $result[] = array('answer' => $answer, 'ttl' => $record['ttl']);
        }

        // return the result
        return $result;
    }

    /**
     * Returns the constant name for the passed DNS record type.
     *
     * @param string $type The type to return the constant for
     *
     * @return string|boolean The constant name for the passed type, or FALSE
     */
    protected function getDnsConstName($type)
    {

        // prepare the constant name and query whether or not it exists
        $constName = "DNS_" . strtoupper($type);
        $name = defined($constName) ? $constName : false;

        // return the name
        return $name;
    }
}
