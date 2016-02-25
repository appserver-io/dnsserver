<?php

/**
 * AppserverIo\DnsServer\Utils\RecordTypeEnum
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

namespace AppserverIo\DnsServer\Utils;

/**
 * Enum implementation for the available DNS record types.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      http://www.appserver.io/
 */
class RecordTypeEnum
{

    /**
     * The available record types.
     *
     * @var integer
     */
    const TYPE_A = 1;
    const TYPE_NS = 2;
    const TYPE_CNAME = 5;
    const TYPE_SOA = 6;
    const TYPE_PTR = 12;
    const TYPE_MX = 15;
    const TYPE_TXT = 16;
    const TYPE_AAAA = 28;
    const TYPE_OPT = 41;
    const TYPE_AXFR = 252;
    const TYPE_ANY = 255;

    /**
     * The type mapping.
     *
     * @var array
     */
    protected static $types = array(
        'A' => 1,
        'NS' => 2,
        'CNAME' => 5,
        'SOA' => 6,
        'PTR' => 12,
        'MX' => 15,
        'TXT' => 16,
        'AAAA' => 28,
        'OPT' => 41,
        'AXFR' => 252,
        'ANY' => 255,
    );

    /**
     * @param integer $typeIndex The index of the type contained in the question
     *
     * @return string|false
     */
    public static function getName($typeIndex)
    {
        return array_search($typeIndex, RecordTypeEnum::$types);
    }

    /**
     * Returns the index for the passed type.
     *
     * @param string $name The name of the record type, e.g. = 'A' or 'MX' or 'SOA'
     *
     * @return integer|false The index or FALSE
     */
    public static function getTypeIndex($name)
    {

        // prepare the key from the passed name
        $key = trim(strtoupper($name));

        // query whether or not, the key exists
        if (!array_key_exists($key, RecordTypeEnum::$types)) {
            return false;
        }

        // return the index
        return RecordTypeEnum::$types[$key];
    }

    /**
     * Return's the available DNS record types.
     *
     * @return array The available types
     */
    public static function getTypes()
    {
        return RecordTypeEnum::$types;
    }
}
