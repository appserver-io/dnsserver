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
     *
     * @var array
     */
    protected $rawData;

    /**
     *
     * @var array
     */
    protected $flags;

    /**
     *
     * @var integer
     */
    protected $offset;

    /**
     *
     * @var array
     */
    protected $question;

    /**
     *
     * @var array
     */
    protected $answer;

    /**
     *
     * @var array
     */
    protected $authority;

    /**
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
     * @param array $data
     */
    public function setRawData(array $rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * @return array
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     *
     * @param string $key
     *
     * @return array
     */
    public function getData($key)
    {
        if (isset($this->rawData[$key])) {
            return $this->rawData[$key];
        }
    }

    /**
     * @param array $authority
     */
    public function setAdditional(array $additional)
    {
        $this->additional = $additional;
    }

    /**
     * @return array
     */
    public function getAdditional()
    {
        return $this->additional;
    }

    /**
     * @param array $authority
     */
    public function setAuthority(array $authority)
    {
        $this->authority = $authority;
    }

    /**
     * @return array
     */
    public function getAuthority()
    {
        return $this->authority;
    }

    /**
     * @param array $answer
     */
    public function setAnswer(array $answer)
    {
        $this->answer = $answer;
    }

    /**
     * @return array
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * @param array $question
     */
    public function setQuestion(array $question)
    {
        $this->question = $question;
    }

    /**
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param integer $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return integer
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     *
     * @param array $flags
     */
    public function setFlags(array $flags)
    {
        $this->flags = $flags;
    }

    /**
     * @return array
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
     * Returns the body content stored in body stream
     *
     * @return string
     */
    public function getBodyContent()
    {
        // init vars
        $bodyContent = "";
        $contentLength = $this->getHeader(HttpProtocol::HEADER_CONTENT_LENGTH);
        // just if we got a body content
        if ($contentLength > 0) {
            // set bodystream resource ref to var
            $bodyStream = $this->getBodyStream();
            // rewind pointer
            rewind($bodyStream);
            // returns whole body content by given content length
            $bodyContent = fread($bodyStream, $contentLength);
        }
        return $bodyContent;
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
