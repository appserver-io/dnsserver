# DNS Server

[![Latest Stable Version](https://img.shields.io/packagist/v/appserver-io/dnsserver.svg?style=flat-square)](https://packagist.org/packages/appserver-io/dnsserver) 
 [![Total Downloads](https://img.shields.io/packagist/dt/appserver-io/dnsserver.svg?style=flat-square)](https://packagist.org/packages/appserver-io/dnsserver)
 [![License](https://img.shields.io/packagist/l/appserver-io/dnsserver.svg?style=flat-square)](https://packagist.org/packages/appserver-io/dnsserver)
 [![Build Status](https://img.shields.io/travis/appserver-io/dnsserver/master.svg?style=flat-square)](http://travis-ci.org/appserver-io/dnsserver)
 [![Code Coverage](https://img.shields.io/codeclimate/github/appserver-io/dnsserver.svg?style=flat-square)](https://codeclimate.com/github/appserver-io/dnsserver)
 [![Code Quality](https://img.shields.io/codeclimate/coverage/github/appserver-io/dnsserver.svg?style=flat-square)](https://codeclimate.com/github/appserver-io/dnsserver)

# Introduction

Are you serious? A web server written in pure PHP for PHP? Ohhhh Yes! :) This is a HTTP/1.1 compliant dnsserver written in php.
And the best... it has a php module and it's multithreaded!

We use this in the [`appserver.io`](<http://www.appserver.io>) project as a server component for handling HTTP requests.

# Installation

If you want to use the web server with your application add this

```sh
{
    "require": {
        "appserver-io/dnsserver": "dev-master"
    }
}
```

to your ```composer.json``` and invoke ```composer update``` in your project.

# Usage

If you can satisfy the requirements it is very simple to use the dnsserver. Just do this:
```bash
git clone https://github.com/appserver-io/dnsserver
cd dnsserver
PHP_BIN=/path/to/your/threadsafe/php-binary bin/dnsserver
```

If you're using [`appserver.io`](<http://www.appserver.io>) the start line will be:
```bash
bin/dnsserver
```

Goto http://127.0.0.1:9080 and if all went good, you will see the welcome page of the php dnsserver.
It will startup on insecure http port 9080 and secure https port 9443.

To test a php script just goto http://127.0.0.1:9080/info.php and see what happens... ;)

# Semantic versioning

This library follows semantic versioning and its public API defines as follows:

* The public API, configuration and entirety of its modules
* The public interface of the `\AppserverIo\dnsserver\ConnectionHandlers\HttpConnectionHandler` class
* The public interfaces within the `\AppserverIo\dnsserver\Interfaces` namespace

# External Links

* Documentation at [appserver.io](http://docs.appserver.io)
