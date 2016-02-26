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

use AppserverIo\DnsServer\Utils\RecordTypeEnum;
use AppserverIo\Server\Interfaces\ModuleConfigurationInterface;

/**
 * A storage provider implementation using a JSON file to load the DNS records from.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      http://www.appserver.io/
 */
class JsonStorageProvider extends AbstractStorageProvider
{

    /**
     * The key for the param containing the name of the file with the DNS records.
     *
     * @var string
     */
    const RECORD_FILE = 'recordFile';

    /**
     * The available DNS records from the JSON file.
     *
     * @var array
     */
    protected $dnsRecords;

    /**
     * The default TTL in seconds for the DNS records.
     *
     * @var integer
     */
    protected $dsTtl;

    /**
     * Initializes the storage provider by loading the configuration values from
     * the passed module configuration.
     *
     * @param \AppserverIo\Server\Interfaces\ModuleConfigurationInterface $moduleConfiguration The module configuration
     *
     * @throws \Exception Is thrown if the JSON can not be read
     */
    public function __construct(ModuleConfigurationInterface $moduleConfiguration)
    {

        // load the configuration values
        $recordFile = $moduleConfiguration->getParam(JsonStorageProvider::RECORD_FILE);
        $defaultTtl = $moduleConfiguration->getParam(AbstractStorageProvider::DEFAULT_TTL);

        // try to open the file
        $handle = @fopen($recordFile, "r");
        if (!$handle) {
            throw new \Exception('Unable to open dns record file.');
        }

        // read the file
        $dnsJson = fread($handle, filesize($recordFile));
        fclose($handle);

        // try to decode the JSON content
        $dnsRecords = json_decode($dnsJson, true);
        if (!$dnsRecords) {
            throw new \Exception('Unable to parse dns record file.');
        }

        // query whether or not the default TTL is an integer
        if (!is_int($defaultTtl)) {
            throw new \Exception('Default TTL must be an integer.');
        }

        // set the default TTL and the DNS records
        $this->dsTtl = $defaultTtl;
        $this->dnsRecords = $dnsRecords;
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

        // initialize the variables
        $answer = array();
        $domain = trim($question[0]['qname'], '.');
        $type = RecordTypeEnum::getName($question[0]['qtype']);

        // query whether the requested domain and type are set in our domain records
        if (isset($this->dnsRecords[$domain]) && isset($this->dnsRecords[$domain][$type])) {
            // query whether or not the type is an array and NOT 'SOA'
            if (is_array($this->dnsRecords[$domain][$type]) && $type != 'SOA') {
                // iterate over the domain's types and load the IP
                foreach ($this->dnsRecords[$domain][$type] as $ip) {
                    $answer[] = array(
                        'name' => $question[0]['qname'],
                        'class' => $question[0]['qclass'],
                        'ttl' => $this->dsTtl,
                        'data' => array('type' => $question[0]['qtype'], 'value' => $ip)
                    );
                }

            } else {
                $answer[] = array(
                    'name' => $question[0]['qname'],
                    'class' => $question[0]['qclass'],
                    'ttl' => $this->dsTtl,
                    'data' => array('type' => $question[0]['qtype'], 'value' => $this->dnsRecords[$domain][$type])
                );
            }
        }

        // return the answer
        return $answer;
    }
}
