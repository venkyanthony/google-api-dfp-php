/**
 * Copyright 2011 Google Inc. All Rights Reserved.
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
 * @fileoverview Main javascript file for the application.
 * @author api.ekoleda@gmail.com (Eric Koleda)
 * @author api.shamjeff@gmail.com (Jeff Sham)
 */

// Declare namespace.
var dfpwebapp = {};

/**
 * Class used to manage panels.
 * @param {Object.<string, Object>} options Map of configuration options.
 * @constructor
 */
dfpwebapp.PanelManager = function(options) {
  /**
   * The class to apply when the panel is loading.
   * @type {string}
   */
  this._loadingClass = options.loadingClass || 'dfp-loading';

  /**
   * The class that identifies the panel's content div.
   * @type {string}
   */
  this._panelContentClass = options.panelContentClass || 'dfp-panel-content';

  /**
   * The class that identifies the panel's div.
   * @type {string}
   */
  this._panelClass = options.panelClass || 'dfp-panel';

  /**
   * The class that identifies the panel's reload link.
   * @type {string}
   */
  this._reloadLinkClass = options.reloadLinkClass || 'dfp-reload';

  /**
   * The class that identifies the panel's expand link.
   * @type {string}
   */
  this._expandLinkClass = options.expandLinkClass || 'dfp-expand';

  /**
   * The class that identifies the panel's filter button.
   * @type {string}
   */
  this._filterButtonClass = options.filterButtonClass || 'dfp-filter-button';

  /**
   * The class to apply when displaying an error message.
   * @type {string}
   */
  this._errorClass = options.errorClass || 'dfp-error';

  /**
   * The class that identifies the filter panel.
   * @type {string}
   */
  this._filterPanelClass = options.filterPanelClass || 'dfp-panel-filter';

  /**
   * The class that identifies the 'content' section of the filter panel.
   * @type {string}
   */
  this._filterPanelContentClass = options.filterPanelContentClass ||
      'dfp-panel-filter-content';

  /**
   * The class that identifies the filter type select.
   * @type {string}
   */
  this._filterSelectClass = options.filterSelectClass || 'dfp-filter-select';

  /**
   * The class that identifies the filter text area.
   * @type {string}
   */
  this._filterTextAreaClass = options.filterSelectClass ||
      'dfp-filter-textarea';

  /**
   * The class that identifies the details link on each item
   * @type {string}
   */
  this._detailsLinkClass = options.detailsLinkClass || '.dfp-details-link';

  /**
   * The class that identifies the sign up warning that shows when the user
   * needs to sign up to sandbox access.
   * @type {string}
   */
  this._signUpWarningId = options.signUpWarningId || '#dfp-sign-up-warning';

  /**
   * A map of panel ids to urls.
   * @type {Object.<string, string>}
   */
  this._panels = {};
};

/**
 * Attach events to panel objects.
 */
dfpwebapp.PanelManager.prototype.init = function() {
  var self = this;

  // Use text from local storage if available.
  if (window.localStorage) {
    $('.' + self._panelClass).each(function() {
      var savedText = window.localStorage.getItem(this.id);
      if (savedText) {
        $('#' + this.id + ' .' + self._filterTextAreaClass).val(savedText);
      }
    });
  }

  // Attach click event to reload links.
  $('.' + self._reloadLinkClass).click(function() {
    self.loadPanel($(this).parents('.' + self._panelClass).attr('id'),
        'default');
    return false;
  });

  // Attach click event to expand links.
  $('.' + self._expandLinkClass).click(function() {
    var contentPanel = $(this).siblings('.' + self._panelContentClass).html();
    var $dialog = $('<div></div>').html(contentPanel).dialog({
      width: 600,
      height: 600,
      title: 'Publisher Query Language Results',
      modal: true,
      autoOpen: false,
      draggable: false
     });
    $dialog.dialog('open');
    return false;
  });

  // Attach click event to filter button.
  $('.' + self._filterButtonClass).click(function() {
    var filterPanelClass = $(this).parents('.' + self._filterPanelClass);
    var filterText = filterPanelClass.find('.' +
        self._filterTextAreaClass).val();
    var typeOverride = filterPanelClass.find('.' +
        self._filterSelectClass).val();
    self.loadPanel($(this).parents('.' + self._panelClass).attr('id'), 'list',
        typeOverride);

    // Save filter text to browser local storage if supported
    if (window.localStorage) {
      window.localStorage.setItem(
          $(this).parents('.' + self._panelClass)[0].id, filterText);
    }
    return false;
  });

  // Attach click event to the filter header.
  $('.' + self._filterPanelClass + ' > h3').click(function() {
    if ($(this).is('.active')) {
      $(this).corner('bottom round 5px');
    } else {
      $(this).uncorner();
    }

    var contentPanel = $(this).parent().siblings('.' + self._panelContentClass);
    // The -10px, +10px is a hack to avoid a layout stutter when animating.
    var fudgeFactor = 10;
    $(contentPanel).height(($(contentPanel).height() - fudgeFactor) + 'px');
    $(contentPanel).animate(
        {'height': ($(this).is('.active') ? '+' : '-') + '=88px'},
        'fast', function() {$(this).height(($(contentPanel).height() +
        fudgeFactor) + 'px')});
    var filterContentPanel =
        $(this).siblings('.' + self._filterPanelContentClass);
    filterContentPanel.slideToggle('fast');
    $(this).toggleClass('active');
  });
};

/**
 * Register a panel id and url with the PanelManager.
 * @param {string} id The id of the panel's div.
 * @param {string} url The url that should be loaded into the panel's content.
 */
dfpwebapp.PanelManager.prototype.registerPanel = function(id, url) {
  this._panels[id] = url;
};

/**
 * Find the div of a given panel, by panel id.
 * @param {string} id The id of the panel.
 * @return {Element} The div of the panel.
 */
dfpwebapp.PanelManager.prototype.findPanel = function(id) {
  return $('#' + id).get(0);
};

/**
 * Find the content div for of a given panel, by panel id.
 * @param {string} id The id of the panel.
 * @return {Element} The div of the panel's content.
 */
dfpwebapp.PanelManager.prototype.findPanelContent = function(id) {
  return $(this.findPanel(id)).children('.' + this._panelContentClass).get(0);
};

/**
 * Find the filter text area for of a given panel, by panel id.
 * @param {string} id The id of the panel.
 * @return {Element} The filter text area of the panel.
 */
dfpwebapp.PanelManager.prototype.findPanelTextArea = function(id) {
  return $(this.findPanel(id)).find('.' + this._filterTextAreaClass).get(0);
};

/**
 * Load all registered panels.
 */
dfpwebapp.PanelManager.prototype.loadAllPanels = function() {
  for (var id in this._panels) {
    this.loadPanel(id, 'default');
  }
};

/**
 * Load a panel with the content from its associated url.
 * @param {string} id The id of the panel.
 * @param {string} displayStyle The display style for the panel.
 * @param {string} typeOverride The type override used when panel can display
 *     when multiple types exist in the panel.
 */
dfpwebapp.PanelManager.prototype.loadPanel = function(id, displayStyle,
    typeOverride) {
  // Preserve reference to this PanelManager object.
  var self = this;
  var url = this._panels[id];
  var panelDiv = this.findPanel(id);
  var panelContentDiv = this.findPanelContent(id);
  var data = {};
  var textArea = this.findPanelTextArea(id);

  if (textArea) {
    data['filterText'] = textArea.value;
  } else {
    data['filterText'] = '';
  }

  if (displayStyle != null) {
    data['displayStyle'] = displayStyle;
  }

  if (typeOverride != null) {
    data['typeOverride'] = typeOverride;
  }

  // Apply the loading class.
  $(panelDiv).addClass(this._loadingClass);
  // Clear old content and load content from the url.
  $(panelContentDiv).html('');
  $(panelContentDiv).load(url, data, function(responseText, textStatus,
      XMLHttpRequest) {
    // Remove loading class.
    $(panelDiv).removeClass(self._loadingClass);
    if (textStatus == 'success') {
      if ($(panelDiv).text().match('NO_NETWORKS_TO_ACCESS')) {
        $(self._signUpWarningId).slideDown('fast');
      } else if ($(panelDiv).text().match('memory\\s+size.*exhausted')){
        $(panelContentDiv).html('<p class="' + self._errorClass + '">' +
          'Result set too large. Please use a lower LIMIT in the statement.' +
          '</p>');
      } else {
        $(self._signUpWarningId).hide();
      }
      // Bind loaded links.
      self.bind(id);
    } else {
      // Request failed, show error message.
      $(panelContentDiv).html('<p class="' + self._errorClass +
          '">Error Loading Panel.</p>');
    }
  });
};

/**
 * Bind actions to the links in the loaded panel HTML.
 * @param {string} id The id of the panel.
 */
dfpwebapp.PanelManager.prototype.bind = function(id) {
  var panelContentDiv = this.findPanelContent(id);

  // Bind details links.
  $(panelContentDiv).find(this._detailsLinkClass).click(function() {
    reference = $(this).attr('rel');
    // isOpen may return the element if the dialog was never opened, so test
    // for something other than the boolean true.
    if ($('#' + reference).dialog('isOpen') !== true) {
      $('#' + reference).dialog({
        width: 600,
        height: 600,
        title: 'Details',
        modal: true,
        autoOpen: false,
        draggable: false
      });
    }
    $('#' + reference).dialog('open');
    return false;
  });
};
