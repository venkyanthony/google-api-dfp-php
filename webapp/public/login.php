<?php
/**
 * Login page of the application, using OAuth.
 *
 * PHP version 5
 *
 * Copyright 2009, Google Inc. All Rights Reserved.
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
 * @package    GoogleApiAdsDfp
 * @subpackage webapp
 * @category   WebServices
 * @copyright  2009, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 * @author     Vincent Tsao <vtsao@google.com>
 */
error_reporting(E_STRICT | E_ALL);

require_once '../lib/ServiceUserManager.php';

if (isset($_REQUEST['oauth_verifier'])) {
  $oauthVerifier = $_REQUEST['oauth_verifier'];
}

if (!isset($oauthVerifier)) {
  // Set the OAuth consumer key and secret. Anonymous values can be used for
  // testing, and real values can be obtained by registering your application:
  // http://code.google.com/apis/accounts/docs/RegistrationForWebAppsAuto.html
  $oauthInfo = array('oauth_consumer_key' => 'anonymous',
      'oauth_consumer_secret' => 'anonymous');

  // Create the DfpUser and set the OAuth info.
  $iniPath = dirname(__FILE__) . '/../';
  $authFile = $iniPath . 'auth.ini';
  $settingsFile = $iniPath . 'settings.ini';
  $user = new DfpUser($authFile, NULL, NULL, NULL, NULL, $settingsFile);
  $user->SetOAuthInfo($oauthInfo);

  // Use the URL of the current page as the callback URL.
  $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
      ? 'https://' : 'http://';
  $server = $_SERVER['HTTP_HOST'];
  $path = $_SERVER["REQUEST_URI"];
  $callbackUrl = $protocol . $server . $path;

  try {
    // Request a new OAuth token. For a web application, pass in the optional
    // callbackUrl parameter to have the user automatically redirected back
    // to your application after authorizing the token.
    $user->RequestOAuthToken($callbackUrl);

    // Get the authorization URL for the OAuth token.
    $location = $user->GetOAuthAuthorizationUrl();
  } catch (OAuthException $e) {
    // Authorization was not granted.
    $error = 'Failed to authenticate: ' .
        str_replace("\n", " ",
            isset($e->lastResponse) ? $e->lastResponse : $e->getMessage());
  }
} else {
  // Get the user from session.
  session_start();
  $user = ServiceUserManager::GetServiceUser();
  session_write_close();

  try {
    // Upgrade the authorized token.
    $user->UpgradeOAuthToken($oauthVerifier);

    // Set network code.
    $networkService = $user->GetService('NetworkService');
    $networks = $networkService->getAllNetworks();
    if (sizeof($networks) > 0) {
      $user->SetNetworkCode($networks[0]->networkCode);
    }

    $location = 'index.php';
  } catch (OAuthException $e) {
    // Authorization was not granted.
    $error = 'Failed to authenticate: ' .
        str_replace("\n", " ",
            isset($e->lastResponse) ? $e->lastResponse : $e->getMessage());
  }
}

if (!isset($error)) {
  // Store the user in session.
  session_start();
  ServiceUserManager::SetServiceUser($user);
  session_write_close();

  // Redirect to application home page.
  Header('Location: ' . $location);
} else {
  // Remove the user from session.
  session_start();
  ServiceUserManager::RemoveServiceUser($user);
  session_write_close();

  // Redirect to application home page.
  Header('Location: index.php?error=' . $error);
}
