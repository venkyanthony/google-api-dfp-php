<?php
/**
 * Main page of the application.
 *
 * PHP version 5
 *
 * Copyright 2011, Google Inc. All Rights Reserved.
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
 * @copyright  2011, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 * @author     Adam Rogal <api.arogal@gmail.com>
 * @author     David Kay <api.dyk@gmail.com>
 * @author     Jeff Sham <api.shamjeff@gmail.com>
 *
 * TODO(api.arogal): Reload button in details dialog box.
 * TODO(api.ekoleda): Fix close link.
 */

error_reporting(E_STRICT | E_ALL);

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';
require_once '../lib/ServiceUserManager.php';
require_once '../lib/WebUtils.php';

// Load the service user from session.
session_start();
try {
  $user = ServiceUserManager::GetServiceUser();
} catch (ValidationException $e) {
  $user = NULL;
}
if (isset($user)) {
  // Load networks.
  $user->SetNetworkCode(NULL);
  $networkService = $user->GetNetworkService();
  $networks = $networkService->getAllNetworks();

  // Change network.
  $network = WebUtils::GetParamOrEmptyString($_GET, 'network');
  if (!empty($network)) {
    $user->SetNetworkCode($network);
    ServiceUserManager::SetServiceUser($user);
  }
}
session_write_close();
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html>
  <head>
    <title>DFP API Sandbox Playground</title>
    <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.13.custom.min.js"></script>
    <script type="text/javascript" src="js/jquery.corner-2.07.js"></script>
    <script type="text/javascript" src="js/main.js"></script>
    <script type="text/javascript">
      // Determine if the user is logged in.
      var isLoggedIn = <?php print $user != NULL ? 'true' : 'false'; ?>;
      // Code to run when page loads.
      $(document).ready(function() {
        $('.dfp-rc-header').corner('5px');
        $('.dfp-panel').corner('5px');
        $('.dfp-panel-filter').corner('bottom round 5px');
        $('.dfp-panel-filter > h3').corner('bottom round 5px');
        $('.dfp-panel-filter-content').corner('bottom round 5px');

        if (isLoggedIn) {
          panelManager = new dfpwebapp.PanelManager({});
          panelManager.init();
          panelManager.registerPanel('dfp-panel-ad-units', 'get/AdUnits.php');
          panelManager.registerPanel('dfp-panel-companies',
              'get/Companies.php');
          panelManager.registerPanel('dfp-panel-creatives',
              'get/Creatives.php');
          panelManager.registerPanel('dfp-panel-creativetemplates',
              'get/CreativeTemplates.php');
          panelManager.registerPanel('dfp-panel-custom-targeting',
              'get/CustomTargeting.php');
          panelManager.registerPanel('dfp-panel-licas', 'get/Licas.php');
          panelManager.registerPanel('dfp-panel-networks', 'get/Networks.php');
          panelManager.registerPanel('dfp-panel-orders', 'get/Orders.php');
          panelManager.registerPanel('dfp-panel-placements',
              'get/Placements.php');
          panelManager.registerPanel('dfp-panel-roles', 'get/Roles.php');
          panelManager.registerPanel('dfp-panel-users', 'get/Users.php');
          panelManager.registerPanel('dfp-panel-pql',
              'get/PublisherQueryLanguage.php');
          panelManager.loadAllPanels();

          // Find the custom targeting filter drop-down
          var dfpFilterSelect = $('#dfp-customtargeting-filter');
          dfpFilterSelect.change(
            // Add a listener to the drop-down box
            function () {
              var state = dfpFilterSelect.val();
              var warning = $('#dfp-customtargeting-warning');
              if (state == 'value') {
                // Make the warning visible
                warning.removeClass('invisible');
                warning.addClass('visible');
              } else {
                // Make the warning invisible
                warning.removeClass('visible');
                warning.addClass('invisible');
              }
            }
          );
        } else {
          setTimeout("$('#dfp-signin-tooltip').fadeIn('slow');", 5000);
        }
      });
    </script>
    <link rel="stylesheet" type="text/css" href="css/main.css" />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui-1.8.13.custom.css" />
  </head>
  <body>
    <div class="dfp-header">
      <!-- Warning to sign up -->
      <div id="dfp-sign-up-warning">
        <span>NO_NETWORKS_TO_ACCESS: If you have not yet, please <a href="http://code.google.com/apis/dfp/docs/signup.html">sign up for the API </a>. You will not need to sign up more than once.</span>
      </div>
      <!-- Account Information -->
      <div id="dfp-account">
        <?php if ($user) { ?>
          <span>Authenticated via OAuth |&nbsp;</span>
          <span>Network: </span>
          <select id="network" onchange="window.location='?network=' + this.value;">
            <?php
              foreach ($networks as $network) {
                $selected = ($user->GetNetworkCode() == $network->networkCode);
                printf('<option value="%s" %s>%s</option>',
                    $network->networkCode,
                    $selected ? 'SELECTED' : '',
                    $network->displayName ? $network->displayName
                        : 'ID: ' . $network->networkCode);
              }
            ?>
          </select>
          <span>|</span>
          <a href="http://code.google.com/apis/dfp/docs/start.html" target="_blank">Documentation</a>
          <span>|</span>
          <a href="http://groups.google.com/group/google-doubleclick-for-publishers-api/topics" target="_blank">Get help</a>
          <span>|</span>
          <a href="http://code.google.com/p/google-api-dfp-php/issues/entry?template=Sandbox%20Playground%20Issue" target="_blank">Report issues</a>
          <span>|</span>
          <a href="http://code.google.com/p/google-api-dfp-php/issues/entry?template=Sandbox%20Playground%20Feedback" target="_blank">Send feedback</a>
          <span>|</span>
          <a href="#" onclick="window.location='logout.php'; return false;">Sign out</a>
        <?php } else { ?>
          <span>Not authenticated |&nbsp;</span>
          <a href="http://code.google.com/apis/dfp/docs/start.html" target="_blank">Documentation</a>
          <span>|</span>
          <a href="http://groups.google.com/group/google-doubleclick-for-publishers-api/topics" target="_blank">Get help</a>
          <span>|</span>
          <a href="http://code.google.com/p/google-api-dfp-php/issues/entry?template=Sandbox%20Playground%20Issue" target="_blank">Report issues</a>
          <span>|</span>
          <a href="http://code.google.com/p/google-api-dfp-php/issues/entry?template=Sandbox%20Playground%20Feedback" target="_blank">Send feedback</a>
          <span>|</span>
          <a href="#" onclick="window.location='login.php'; return false;">Sign in</a>
        <?php } ?>
      </div>
      <h1 id="dfp-logo">Sandbox Playground</h1>
    </div>
    <hr/>
    <div class="dfp-content">
      <!-- Panels container -->
      <div id="dfp-panels">
        <!-- Ad Units -->
        <div id="dfp-panel-ad-units" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>Ad Units</h2>
          </div>
          <div class="dfp-panel-content"></div>
          <div class="dfp-panel-filter">
            <a class="dfp-reference-link" target="_blank"
                href="http://code.google.com/apis/dfp/docs/reference/latest/InventoryService.html#getAdUnitsByStatement">Reference</a>
            <h3>Filter</h3>
            <div class="dfp-panel-filter-content">
              <textarea class="dfp-filter-textarea">WHERE id != 0 LIMIT 500 OFFSET 0</textarea>
              <div class="dfp-filter-footer">
                <button class="dfp-filter-button">Filter</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Companies -->
        <div id="dfp-panel-companies" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>Companies</h2>
          </div>
          <div class="dfp-panel-content"></div>
          <div class="dfp-panel-filter">
            <a class="dfp-reference-link" target="_blank"
                href="http://code.google.com/apis/dfp/docs/reference/latest/CompanyService.html#getCompaniesByStatement">Reference</a>
            <h3>Filter</h3>
            <div class="dfp-panel-filter-content">
              <textarea class="dfp-filter-textarea">WHERE id != 0 LIMIT 500 OFFSET 0</textarea>
              <div class="dfp-filter-footer">
                <button class="dfp-filter-button">Filter</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Creatives -->
        <div id="dfp-panel-creatives" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>Creatives</h2>
          </div>
          <div class="dfp-panel-content"></div>
          <div class="dfp-panel-filter">
            <a class="dfp-reference-link" target="_blank"
                href="http://code.google.com/apis/dfp/docs/reference/latest/CreativeService.html#getCreativesByStatement">Reference</a>
            <h3>Filter</h3>
            <div class="dfp-panel-filter-content">
              <textarea class="dfp-filter-textarea">WHERE id != 0 LIMIT 500 OFFSET 0</textarea>
              <div class="dfp-filter-footer">
                <button class="dfp-filter-button">Filter</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Creative Templates -->
        <div id="dfp-panel-creativetemplates" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>Creative Templates</h2>
          </div>
          <div class="dfp-panel-content"></div>
          <div class="dfp-panel-filter">
            <a class="dfp-reference-link" target="_blank"
                href="http://code.google.com/apis/dfp/docs/reference/latest/CreativeTemplateService.html#getCreativeTemplatesByStatement">Reference</a>
            <h3>Filter</h3>
            <div class="dfp-panel-filter-content">
              <textarea class="dfp-filter-textarea">WHERE id != 0 LIMIT 500 OFFSET 0</textarea>
              <div class="dfp-filter-footer">
                <button class="dfp-filter-button">Filter</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Custom Targeting -->
        <div id="dfp-panel-custom-targeting" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>Custom Targeting</h2>
          </div>
          <div class="dfp-panel-content"></div>
          <div class="dfp-panel-filter">
            <a class="dfp-reference-link" target="_blank"
                href="http://code.google.com/apis/dfp/docs/reference/latest/CustomTargetingService.html#getCustomTargetingKeysByStatement">Reference</a>
            <h3>Filter</h3>
            <div class="dfp-panel-filter-content">
              <label id="dfp-customtargeting-warning" class="invisible">Note: You must specify <strong><a href="http://code.google.com/apis/dfp/docs/reference/latest/CustomTargetingService.html#getCustomTargetingValuesByStatement">"customTargetingKeyId IN (...)"</a></strong><br /></label>
              <textarea class="dfp-filter-textarea">WHERE id != 0 LIMIT 500 OFFSET 0</textarea>
              <div class="dfp-filter-footer">
                <label>Filter on</label>
                <select id="dfp-customtargeting-filter" class="dfp-filter-select" >
                  <option value="key">Keys</option>
                  <option value="value">Values</option>
                </select>
                <button class="dfp-filter-button">Filter</button>
              </div>
            </div>
          </div>
        </div>

        <!-- LICAs -->
        <div id="dfp-panel-licas" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>LICAs</h2>
          </div>
          <div class="dfp-panel-content"></div>
          <div class="dfp-panel-filter">
            <a class="dfp-reference-link" target="_blank"
                href="http://code.google.com/apis/dfp/docs/reference/latest/LineItemCreativeAssociationService.html#getLineItemCreativeAssociationsByStatement">Reference</a>
            <h3>Filter</h3>
            <div class="dfp-panel-filter-content">
              <textarea class="dfp-filter-textarea">WHERE lineitemid != 0 LIMIT 500 OFFSET 0</textarea>
              <div class="dfp-filter-footer">
                <button class="dfp-filter-button">Filter</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Networks -->
        <div id="dfp-panel-networks" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>Networks</h2>
          </div>
          <div class="dfp-panel-content"></div>
        </div>

        <!-- Orders and Line Items -->
        <div id="dfp-panel-orders" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>Orders and Line Items</h2>
          </div>
          <div class="dfp-panel-content"></div>
          <div class="dfp-panel-filter">
            <span class="dfp-reference-link">Reference:
              <a target="_blank"
                  href="http://code.google.com/apis/dfp/docs/reference/latest/LineItemService.html#getLineItemsByStatement">LineItem</a>
              <a target="_blank"
                  href="http://code.google.com/apis/dfp/docs/reference/latest/OrderService.html#getOrdersByStatement">Order</a>
            </span>
            <h3>Filter</h3>
            <div class="dfp-panel-filter-content">
              <textarea class="dfp-filter-textarea">WHERE name != '' LIMIT 500 OFFSET 0</textarea>
              <div class="dfp-filter-footer">
                <label>Filter on</label>
                <select class="dfp-filter-select" >
                  <option value="lineitem">Line Items</option>
                  <option value="order">Orders</option>
                </select>
                <button class="dfp-filter-button">Filter</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Placements -->
        <div id="dfp-panel-placements" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>Placements</h2>
          </div>
          <div class="dfp-panel-content"></div>
          <div class="dfp-panel-filter">
            <a class="dfp-reference-link" target="_blank"
                href="http://code.google.com/apis/dfp/docs/reference/latest/PlacementService.html#getPlacementsByStatement">Reference</a>
            <h3>Filter</h3>
            <div class="dfp-panel-filter-content">
              <textarea class="dfp-filter-textarea">WHERE id != 0 LIMIT 500 OFFSET 0</textarea>
              <div class="dfp-filter-footer">
                <button class="dfp-filter-button">Filter</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Roles -->
        <div id="dfp-panel-roles" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>Roles</h2>
          </div>
          <div class="dfp-panel-content"></div>
        </div>

        <!-- Users -->
        <div id="dfp-panel-users" class="dfp-panel">
          <a class="dfp-reload" href="#">Reload</a>
          <div class="dfp-rc-header">
            <h2>Users</h2>
          </div>
          <div class="dfp-panel-content"></div>
          <div class="dfp-panel-filter">
            <a class="dfp-reference-link" target="_blank"
                href="http://code.google.com/apis/dfp/docs/reference/latest/UserService.html#getUsersByStatement">Reference</a>
            <h3>Filter</h3>
            <div class="dfp-panel-filter-content">
              <textarea class="dfp-filter-textarea">WHERE id != 0 LIMIT 500 OFFSET 0</textarea>
              <div class="dfp-filter-footer">
                <button class="dfp-filter-button">Filter</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Publisher Query Language -->
        <div id="dfp-panel-pql" class="dfp-panel">
          <a class="dfp-expand" href="#">Expand</a>
          <div class="dfp-rc-header">
            <h2>Publisher Query Language</h2>
          </div>
          <div class="dfp-panel-content"></div>
          <div class="dfp-panel-filter">
            <a class="dfp-reference-link" target="_blank"
                href="http://code.google.com/apis/dfp/docs/reference/latest/PublisherQueryLanguageService.html">Reference</a>
            <h3>Select Statement</h3>
            <div class="dfp-panel-filter-content">
              <textarea class="dfp-filter-textarea">SELECT * FROM Browser</textarea>
              <div class="dfp-filter-footer">
                <button class="dfp-filter-button">Select</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="dfp-footer">
    Created using Google"s DoubleClick for Publishers API. &#169;2011 Google Inc. All rights reserved. Google and DoubleClick are trademarks of Google Inc.
    </div>
    <div id="dfp-signin-tooltip">Click "Sign in" to get started.</div>
    <script type="text/javascript">
      var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
      document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
    </script>
    <script type="text/javascript">
      try {
        var pageTracker = _gat._getTracker("UA-9603638-2");
        pageTracker._trackPageview();
      } catch(err) {}
    </script>
  </body>
</html>
