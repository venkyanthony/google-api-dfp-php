# Using the DFP API in PHP without the client library #

We highly recommend using our client library to interface with the DFP API if you're using PHP. However, we understand that sometimes this may not be possible depending on the PHP framework you're using. This guide provides a basic example on how to get started without the client library. However, we do not officially support this method of interfacing with the DFP API using PHP.

The example requires the following:
  * [PHP 5.2.11](http://php.net/releases/5_2_11.php) or later.
  * [SoapClient](http://us3.php.net/manual/en/book.soap.php).
  * [OpenSSL](http://php.net/manual/en/book.openssl.php).
  * Your OAuth 2 access token for authenticating with DFP.

If you don't have a way of obtaining an access token, you can use the OAuth 2 handler utility we provide in our client library here:
  * [SimpleOAuth2Handler.php](https://code.google.com/p/google-api-dfp-php/source/browse/src/Google/Api/Ads/Common/Util/SimpleOAuth2Handler.php)

The SimpleOAuth2Handler class will require you have cURL:
  * [cURL](http://php.net/manual/en/book.curl.php)


## Authentication ##

Authentication in the DFP API is handled using [OAuth 2](https://developers.google.com/doubleclick-publishers/docs/authentication#oauth).

This guide assumes that you already have a client ID, client secret, and refresh token ready for OAuth 2 authentication. If you need help on obtaining those, take a look at our [GetRefreshToken.php](https://code.google.com/p/google-api-dfp-php/source/browse/examples/Dfp/Auth/GetRefreshToken.php) example.

This guide also assumes that you are able to exchange your client ID, client secret, and refresh token for an OAuth 2 access token. But if you do not have code in place for that, we have provided a basic example using our [SimpleOAuth2Handler.php](https://code.google.com/p/google-api-dfp-php/source/browse/src/Google/Api/Ads/Common/Util/SimpleOAuth2Handler.php) utility:

```
// Provide your offline OAuth 2 credentials. For more information about where to
// obtain these credentials, look at GetRefreshToken.php.
define('CLIENT_ID', 'INSERT_CLIENT_ID_HERE');
define('CLIENT_SECRET', 'INSERT_CLIENT_SECRET_HERE');
define('REFRESH_TOKEN', 'INSERT_REFRESH_TOKEN_HERE');

// Set the path to the SimpleOAuth2Handler.php file.
$path = 'path/to/oauth/handler';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once 'SimpleOAuth2Handler.php';

// Google OAuth 2 constants.
define('OAUTH2_SCOPE', 'https://www.google.com/apis/ads/publisher');
define('AUTH_SERVER', 'https://accounts.google.com');

// Obtain an access token.
$oauth2Handler = new SimpleOAuth2Handler(AUTH_SERVER, OAUTH2_SCOPE);
$credentials = array('client_id' => CLIENT_ID,
    'client_secret' => CLIENT_SECRET,
    'refresh_token' => REFRESH_TOKEN);
$credentials = $oauth2Handler->GetOrRefreshAccessToken($credentials);
printf("%s\n", $credentials['access_token']);
```


## Creating the SOAP client service ##

Once you have your authentication information setup, we can create the SoapClient that will allow us to interface with the DFP API.

First we'll setup all the required credentials and constants. For this example we'll be interfacing specifically with the [OrderService](https://developers.google.com/doubleclick-publishers/docs/reference/latest/OrderService).

```
// Provide the following credentials for this example.
$networkCode = 'INSERT_NETWORK_CODE_HERE';
$accessToken = 'INSERT_ACCESS_TOKEN_HERE';

// Constants for this example.
define('ORDER_SERVER_WSDL',
    'https://www.google.com/apis/ads/publisher/v201308/OrderService?wsdl');
define('V201308_NAMESPACE',
    'https://www.google.com/apis/ads/publisher/v201308');
define('APPLICATION_NAME', 'PHP no client library example');
```

Then we'll create the HTTP header for OAuth 2 authentication.

```
// Create the stream context containing HTTP header for OAuth 2 authentication.
$context = array('http' => array('header' => sprintf("Authorization: Bearer %s",
    $accessToken)));
```

We create a SoapClient that represents the OrderService with that HTTP header and some basic options.

```
// Create a SoapClient to interface with the OrderService.
$orderService = new SoapClient(ORDER_SERVER_WSDL, array('trace' => TRUE,
    'encoding' => 'UTF-8',
    'connection_timeout' => 0,
    'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
    'stream_context' => stream_context_create($context)));
```

Then finally we'll set the required SOAP headers on the OrderService SoapClient.

```
// Create and set SOAP headers.
$headers = new SoapHeader(V201308_NAMESPACE, 'RequestHeader', array(
    'networkCode' => $networkCode,
    'applicationName' => APPLICATION_NAME));
$orderService->__setSoapHeaders($headers);
```


## Making a method call ##

Now that we have the SoapClient setup, we can make a method call using it. We'll be calling [getOrdersByStatement](https://developers.google.com/doubleclick-publishers/docs/reference/latest/OrderService#getOrdersByStatement) in this example.

First we prepare the arguments this method takes, which is a filterStatement.

```
// Create a statement to only select the latest ten orders in your network.
$filterStatement = array('query' => 'ORDER BY Id DESC LIMIT 10');

$args = array('filterStatement' => $filterStatement);
```

Then we can call the method as if it existed on the SoapClient class because we created the SoapClient in WSDL mode.

```
try {
  // Using SoapClient in WSDL mode.
  $result = $orderService->getOrdersByStatement($args);
} catch (SoapFault $e) {
  print_r($e);
}
```


## Handling the results ##

The result of a method call will be a wrapper object with a single filed called "rval". This field will contain the actual results, as defined by the API. The results are StdClass objects with the appropriate fields.

```
// Unwrap results.
$page = $result->rval;

if (!empty($page->results)) {
  $i = $page->startIndex;
  foreach ($page->results as $order) {
    printf("%d) Order with ID '%d', name '%s', and advertiser ID '%d' "
        . "was found.\n", $i++, $order->id, $order->name, $order->advertiserId);
  }
}

printf("Number of results found: %d\n", $page->totalResultSetSize);

```


## The complete example ##

```
<?php
/**
 * This example shows how to interface with the DFP API without using the client
 * library. It retrieves ten orders and prints basic details about them.
 *
 * PHP version 5
 *
 * Copyright 2013, Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright  2013, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License,
 *             Version 2.0
 * @author     Vincent Tsao
 */
error_reporting(E_STRICT | E_ALL);

// Provide the following credentials for this example.
$networkCode = 'INSERT_NETWORK_CODE_HERE';
$accessToken = 'INSERT_ACCESS_TOKEN_HERE';

// Constants for this example.
define('ORDER_SERVER_WSDL',
    'https://www.google.com/apis/ads/publisher/v201308/OrderService?wsdl');
define('V201308_NAMESPACE',
    'https://www.google.com/apis/ads/publisher/v201308');
define('APPLICATION_NAME', 'PHP no client library example');

// Create the stream context containing HTTP header for OAuth 2 authentication.
$context = array('http' => array('header' => sprintf("Authorization: Bearer %s",
    $accessToken)));

// Create a SoapClient to interface with the OrderService.
$orderService = new SoapClient(ORDER_SERVER_WSDL, array('trace' => TRUE,
    'encoding' => 'utf-8',
    'connection_timeout' => 0,
    'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
    'stream_context' => stream_context_create($context)));

// Create and set SOAP headers.
$headers = new SoapHeader(V201308_NAMESPACE, 'RequestHeader', array(
    'networkCode' => $networkCode,
    'applicationName' => APPLICATION_NAME));
$orderService->__setSoapHeaders($headers);

// Create a statement to only select the latest ten orders in your network.
$filterStatement = array('query' => 'ORDER BY Id DESC LIMIT 10');

$args = array('filterStatement' => $filterStatement);

try {
  // Using SoapClient in WSDL mode.
  $result = $orderService->getOrdersByStatement($args);
} catch (SoapFault $e) {
  print_r($e);
}

// Unwrap results.
$page = $result->rval;

if (!empty($page->results)) {
  $i = $page->startIndex;
  foreach ($page->results as $order) {
    printf("%d) Order with ID '%d', name '%s', and advertiser ID '%d' "
        . "was found.\n", $i++, $order->id, $order->name, $order->advertiserId);
  }
}

printf("Number of results found: %d\n", $page->totalResultSetSize);
```

If you have any questions or comments about this example, we invite you to discuss this on our [DFP API forums](https://groups.google.com/forum/?fromgroups=#!forum/google-doubleclick-for-publishers-api). However, as mentioned before, we do not officially support this route of not using the PHP client library so support on this will be limited to this guide.