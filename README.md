# DNS Server

[![Latest Stable Version](https://img.shields.io/packagist/v/appserver-io/dnsserver.svg?style=flat-square)](https://packagist.org/packages/appserver-io/dnsserver) 
 [![Total Downloads](https://img.shields.io/packagist/dt/appserver-io/dnsserver.svg?style=flat-square)](https://packagist.org/packages/appserver-io/dnsserver)
 [![License](https://img.shields.io/packagist/l/appserver-io/dnsserver.svg?style=flat-square)](https://packagist.org/packages/appserver-io/dnsserver)
 [![Build Status](https://img.shields.io/travis/appserver-io/dnsserver/master.svg?style=flat-square)](http://travis-ci.org/appserver-io/dnsserver)
 [![Code Coverage](https://img.shields.io/codeclimate/github/appserver-io/dnsserver.svg?style=flat-square)](https://codeclimate.com/github/appserver-io/dnsserver)
 [![Code Quality](https://img.shields.io/codeclimate/coverage/github/appserver-io/dnsserver.svg?style=flat-square)](https://codeclimate.com/github/appserver-io/dnsserver)

# Introduction

Are you serious? A DNS server written in pure PHP for PHP? Ohhhh Yes! :)

We use this in the [`appserver.io`](<http://www.appserver.io>) project as a server component for handling DNS requests. The purpose to
implement a DNS server is to deliver it with appserver.io and allow automatich DNS resolution for the defined virtual hosts.

# Installation

If you want to use the DNS server with your application add this

```sh
{
    "require": {
        "appserver-io/dnsserver": "dev-master"
    }
}
```

to your ```composer.json``` and invoke ```composer update``` in your project.

# Usage

If you can satisfy the requirements it is very simple to use the DNS server. Just do this:
```bash
git clone https://github.com/appserver-io/dnsserver
cd dnsserver
PHP_BIN=/path/to/your/threadsafe/php-binary bin/dnsserver
```

If you're using [`appserver.io`](<http://www.appserver.io>) the start line will be:
```bash
bin/dnsserver
```

Open a console and enter

```sh
console$ dig @127.0.0.1 test.com A +short
```

the output should be

```sh
111.111.111.111
```

which is the IP v4 address for the domain test.com, defined in the file `etc/dns_record.json` ;)

# External Links

* Documentation at [appserver.io](http://docs.appserver.io)
