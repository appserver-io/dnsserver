<?php

/**
 * AppserverIo\DnsServer\Connectors\DnsRequest
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

namespace AppserverIo\DnsServer\Connectors;

use AppserverIo\DnsServer\Interfaces\DnsRequestInterface;

/**
 * A DNS request implementation.
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2016 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/dnsserver
 * @link      https://www.appserver.io
 */
class DnsRequest implements DnsRequestInterface
{

    /**
     * Holds the file descriptor resource to body stream
     *
     * @var resource
     */
    protected $bodyStream;

    /**
     * The raw DNS data passed by the client.
     *
     * @var array
     */
    protected $rawData;

    /**
     * The DNS flags sent by the client.
     *
     * @var array
     */
    protected $flags;

    /**
     * The offset to start reading the DNS request data.
     *
     * @var integer
     */
    protected $offset;

    /**
     * The DNS request information.
     *
     * @var array
     */
    protected $question;

    /**
     * The DNS authority passed by the client.
     *
     * @var array
     */
    protected $answer;

    /**
     * The DNS authority passed by the client.
     *
     * @var array
     */
    protected $authority;

    /**
     * The additional DNS data passed by the client.
     *
     * @var array
     */
    protected $additional;

    /**
     * Constructs the request object
     */
    public function __construct()
    {
        $this->resetBodyStream();
    }

    /**
     * Set's the raw DNS data passed by the client.
     *
     * @param array $rawData The raw DNS data
     *
     * @return void
     */
    public function setRawData(array $rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * Return's the raw DNS data passed by the client.
     *
     * @return array The raw DNS data
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * Return's the DNS data with the passed key from the raw data.
     *
     * @param string $key The key of the data to be returned
     *
     * @return array|null The requested data
     */
    public function getData($key)
    {
        if (isset($this->rawData[$key])) {
            return $this->rawData[$key];
        }
    }

    /**
     * Set's additional DNS data passed by the client.
     *
     * @param array $additional The additional data
     *
     * @return void
     */
    public function setAdditional(array $additional)
    {
        $this->additional = $additional;
    }

    /**
     * Return's additional DNS data passed by the client.
     *
     * @return array The additional data
     */
    public function getAdditional()
    {
        return $this->additional;
    }

    /**
     * Set's the DNS authority passed by the client.
     *
     * @param array $authority The authority
     *
     * @return void
     */
    public function setAuthority(array $authority)
    {
        $this->authority = $authority;
    }

    /**
     * Return's the DNS authority passed by the client.
     *
     * @return array The authority
     */
    public function getAuthority()
    {
        return $this->authority;
    }

    /**
     * Set's the DNS request answer.
     *
     * @param array $answer The answer
     *
     * @return void
     */
    public function setAnswer(array $answer)
    {
        $this->answer = $answer;
    }

    /**
     * Return's the DNS request answer.
     *
     * @return array The answer
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Set's the DNS request information.
     *
     * @param array $question The DNS request information
     *
     * @return void
     */
    public function setQuestion(array $question)
    {
        $this->question = $question;
    }

    /**
     * Return's the DNS request query information.
     *
     * @return string The DNS request information
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Set's the offset to start reading the DNS request data.
     *
     * @param integer $offset The offset
     *
     * @return void
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * Return's the offset to start reading the DNS request data.
     *
     * @return integer The offset
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Set's the DNS flags sent by the client.
     *
     * @param array $flags The flags
     *
     * @return void
     */
    public function setFlags(array $flags)
    {
        $this->flags = $flags;
    }

    /**
     * Return's the DNS flags sent by the client.
     *
     * @return array The flags
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Inits the body stream
     *
     * @return void
     */
    protected function resetBodyStream()
    {

        // if body stream exists close it
        if (is_resource($this->bodyStream)) {
            fclose($this->bodyStream);
        }
        $this->setBodyStream(fopen('php://memory', 'w+'));
    }

    /**
     * Initialises the request object to default properties
     *
     * @return void
     */
    public function init()
    {

        // init body stream
        $this->resetBodyStream();

        // intialize the members
        $this->offset = 12;
        $this->flags = array();
        $this->question = array();
        $this->authority = array();
        $this->answer = array();
        $this->additional = array();

        // return the instance
        return $this;
    }

    /**
     * Resets the stream resource pointing to body content
     *
     * @param resource $bodyStream The body content stream resource
     *
     * @return void
     */
    public function setBodyStream($bodyStream)
    {
        // check if old body stream is still open
        if (is_resource($this->bodyStream)) {
            // close it before
            fclose($this->bodyStream);
        }
        $this->bodyStream = $bodyStream;
    }

    /**
     * Returns the stream resource pointing to body content
     *
     * @return resource The body content stream resource
     */
    public function getBodyStream()
    {
        return $this->bodyStream;
    }

    /**
     * Copies a source stream to body stream
     *
     * @param resource $sourceStream The file pointer to source stream
     * @param int      $maxlength    The max length to read from source stream
     * @param int      $offset       The offset from source stream to read
     *
     * @return int the total count of bytes copied.
     */
    public function copyBodyStream($sourceStream, $maxlength = null, $offset = null)
    {
        // check if offset is given without maxlength
        if ($offset && !$maxlength) {
            throw new \InvalidArgumentException('offset can not be without a maxlength');
        }

        // first rewind sourceStream if its seekable
        $sourceStreamMetaData = stream_get_meta_data($sourceStream);
        if ($sourceStreamMetaData['seekable']) {
            rewind($sourceStream);
        }

        if ($offset && $maxlength) {
            return stream_copy_to_stream($sourceStream, $this->getBodyStream(), $maxlength, $offset);
        }
        if (!$offset && $maxlength) {
            return stream_copy_to_stream($sourceStream, $this->getBodyStream(), $maxlength);
        }
        // and finally
        return stream_copy_to_stream($sourceStream, $this->getBodyStream());
    }

    /**
     * Appends body stream with content
     *
     * @param string $content The content to append
     *
     * @return int
     */
    public function appendBodyStream($content)
    {
        return fwrite($this->getBodyStream(), $content);
    }
}
