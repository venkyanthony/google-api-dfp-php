The client library now supports most standard distributions of PHP 5.2.x - 5.3.x: **5.2.0 - latest 5.3.x**

If your distribution of PHP does not work, please list it below in the comments section along with your Operating System and any other pieces of valuable information.

### Minimum required PHP extensions ###
  * SoapClient http://us3.php.net/manual/en/book.soap.php (--enable-soap)
  * OpenSSL http://php.net/manual/en/book.openssl.php (--with-ssl)
  * cURL http://php.net/manual/en/book.curl.php (--with-curl)

### Known issues ###

**PHP Suhosin Patch**

With some PHP installations, it has been found that the Suhosin patch prevents
correct usage of the AdWords API PHP Client Library. It is believed that the
patch is catching memory leaks caused by an underlying library, but we are
still investigating the root cause. Errors caught by the Suhosin patch may
look like the following:

> ALERT - canary mismatch on efree() - heap overflow detected
> (attacker 'REMOTE\_ADDR not set', file '...', line ...)

At this time we recommend using versions of PHP that do not have the Suhosin
patch applied. More information about the Suhosin patch can be found here:

> http://www.hardened-php.net/suhosin/index.html

Note: this patch is applied by default to many standard distributions, including
the current Ubuntu distribution - **5.2.4-2ubuntu5.7**.