/*
 * Mentions Input
 * Version 1.0.0
 * Written by: Georgi Nachev (jooorooo / jooorooo@gmail.com)
 *
 *
 * License: MIT License - http://www.opensource.org/licenses/mit-license.php
 */

(function ($) {

  // Settings
  var T = $.joFunctions;
  var KEY = { BACKSPACE : 8, TAB : 9, RETURN : 13, ESC : 27, LEFT : 37, UP : 38, RIGHT : 39, DOWN : 40, COMMA : 188, SPACE : 32, HOME : 36, END : 35 }; // Keys "enum"
  var defaultSettings = {
    triggerChar   : '@',
    onDataRequest : $.noop,
    minChars      : 2,
    showAvatars   : true,
    elastic       : true,
    elasticCallback : false,
    classes       : {
      autoCompleteItemActive : "active",
      autoCompleteUL: 'autoComplete-list'
    },
    templates     : {
      wrapper                    : '<div class="mentions-input-box"></div>',
      autocompleteList           : '<div class="mentions-autocomplete-list"></div>',
      autocompleteListItem       : '<li data-ref-id="${id}" data-ref-type="${type}" data-display="${display}">${content}</li>',
      autocompleteListItemAvatar : '<img  src="${avatar}" />',
      autocompleteListItemIcon   : '<div class="icon ${icon}"></div>',
      mentionsOverlay            : '<div class="mentions"></div>',
      mentionItemSyntax          : '@[${value}](${type}:${id})',
      mentionItemHighlight       : '<strong><span>${value}</span></strong>'
    }
  };

  var utils = {
    htmlEncode       : function (str) {
      return T.escape(str);
    },
    highlightTerm    : function (value, term) {
      if (!term && !term.length) {
        return value;
      }
      return value.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + term + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<b>$1</b>");
    },
    setCaratPosition : function (domNode, caretPos) {
      if (domNode.createTextRange) {
        var range = domNode.createTextRange();
        range.move('character', caretPos);
        range.select();
      } else {
        if (domNode.selectionStart) {
          domNode.focus();
          domNode.setSelectionRange(caretPos, caretPos);
        } else {
          domNode.focus();
        }
      }
    },
    rtrim: function(string) {
      return string.replace(/\s+$/,"");
    }
  };

  var MentionsInput = function (settings) {

    var domInput, elmInputBox, elmInputWrapper, elmAutocompleteList, elmWrapperBox, elmMentionsOverlay, elmActiveAutoCompleteItem;
    var mentionsCollection = [];
    var autocompleteItemCollection = {};
    var inputBuffer = [];
    var currentDataQuery;

    settings = $.extend(true, {}, defaultSettings, settings );

    function initTextarea() {
      elmInputBox = $(domInput);

      if (elmInputBox.attr('data-mentions-input') == 'true') {
        return;
      }

      elmInputWrapper = elmInputBox.parent();
      elmWrapperBox = $(settings.templates.wrapper);
      elmInputBox.wrapAll(elmWrapperBox);
      elmWrapperBox = elmInputWrapper.find('.mentions-input-box');

      elmInputBox.attr('data-mentions-input', 'true');
      elmInputBox.bind('keydown', onInputBoxKeyDown);
      elmInputBox.bind('keypress', onInputBoxKeyPress);
      elmInputBox.bind('input', onInputBoxInput);
      elmInputBox.bind('click', onInputBoxClick);
      elmInputBox.bind('blur', onInputBoxBlur);

      // Elastic textareas, internal setting for the Dispora guys
      if( settings.elastic && $.isFunction($.fn.elastic)) {
        elmInputBox.elastic(function(){ 
        	settings.elasticCallback();
        	elmMentionsOverlay.height(elmInputBox.height());
        });
      }

    }

    function initAutocomplete() {
      elmAutocompleteList = $(settings.templates.autocompleteList);
      elmAutocompleteList.appendTo(elmWrapperBox);
      elmAutocompleteList.delegate('li', 'mousedown', onAutoCompleteItemClick);
    }

    function initMentionsOverlay() {
      elmMentionsOverlay = $(settings.templates.mentionsOverlay);
	  init_hiliter(elmInputBox);
      elmMentionsOverlay.insertBefore(elmInputBox);
    }

	function makeReplace(tpl, data) {
	
		function regex_escape(text) {
			return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
		}
	
		for( i in data ) {
			var regex = new RegExp(regex_escape("${" + i + "}"), "gi");
			tpl = tpl.replace(regex, data[i]);
		}
		return tpl;
	}
	
    function updateValues() {
      var syntaxMessage = getInputBoxValue();

      $.each(mentionsCollection, function (i, mention) {
		var textSyntax = makeReplace(settings.templates.mentionItemSyntax, mention);
        syntaxMessage = syntaxMessage.replace(mention.value, textSyntax);
      });

      var mentionText = utils.htmlEncode(syntaxMessage);

      $.each(mentionsCollection, function (i, mention) {
        var formattedMention = $.extend({}, mention, {value: utils.htmlEncode(mention.value)});
        var textSyntax = makeReplace(settings.templates.mentionItemSyntax, formattedMention);
        var textHighlight = makeReplace(settings.templates.mentionItemHighlight, formattedMention);
        mentionText = mentionText.replace(textSyntax, textHighlight);
      });

      mentionText = mentionText.replace(/\n/g, '<br />');
      mentionText = mentionText.replace(/ {2}/g, '&nbsp; ');

      elmInputBox.data('messageText', syntaxMessage);
      elmMentionsOverlay.html(mentionText);
    }

    function resetBuffer() {
      inputBuffer = [];
    }

    function updateMentionsCollection() {
      var inputText = getInputBoxValue();

      mentionsCollection = T.reject(mentionsCollection, function (mention, index) {
        return !mention.value || inputText.indexOf(mention.value) == -1;
      });
      mentionsCollection = T.compact(mentionsCollection);
    }

    function addMention(mention) {

      var currentMessage = getInputBoxValue();

      // Using a regex to figure out positions
      var regex = new RegExp("\\" + settings.triggerChar + currentDataQuery, "gi");
      regex.exec(currentMessage);

      var startCaretPosition = regex.lastIndex - currentDataQuery.length - 1;
      var currentCaretPosition = regex.lastIndex;

      var start = currentMessage.substr(0, startCaretPosition);
      var end = currentMessage.substr(currentCaretPosition, currentMessage.length);
      var startEndIndex = (start + mention.value).length + 1;

      mentionsCollection.push(mention);

      // Cleaning before inserting the value, otherwise auto-complete would be triggered with "old" inputbuffer
      resetBuffer();
      currentDataQuery = '';
      hideAutoComplete();

      // Mentions & syntax message
      
      var updatedMessageText = start + mention.value + ' ' + end;
      elmInputBox.val(updatedMessageText);
      updateValues();

      // Set correct focus and selection
      elmInputBox.focus();
      utils.setCaratPosition(elmInputBox[0], startEndIndex);
    }

    function getInputBoxValue() {
      return $.trim(elmInputBox.val());
    }

    function onAutoCompleteItemClick(e) {
      var elmTarget = $(this);
      var mention = autocompleteItemCollection[elmTarget.attr('data-uid')];

      addMention(mention);

      return false;
    }

    function onInputBoxClick(e) {
      resetBuffer();
    }

    function onInputBoxBlur(e) {
      hideAutoComplete();
    }

    function onInputBoxInput(e) {
      updateValues();
      updateMentionsCollection();
      hideAutoComplete();

      var triggerCharIndex = T.lastIndexOf(inputBuffer, settings.triggerChar);
      if (triggerCharIndex > -1) {
        currentDataQuery = inputBuffer.slice(triggerCharIndex + 1).join('');
        currentDataQuery = utils.rtrim(currentDataQuery);

        T.defer(T.bind(doSearch, this, currentDataQuery));
      }
    }

    function onInputBoxKeyPress(e) {
      if(e.keyCode !== KEY.BACKSPACE) {
        var typedValue = String.fromCharCode(e.which || e.keyCode);
        inputBuffer.push(typedValue);
      }
    }

    function onInputBoxKeyDown(e) {

      // This also matches HOME/END on OSX which is CMD+LEFT, CMD+RIGHT
      if (e.keyCode == KEY.LEFT || e.keyCode == KEY.RIGHT || e.keyCode == KEY.HOME || e.keyCode == KEY.END) {
        // Defer execution to ensure carat pos has changed after HOME/END keys
        T.defer(resetBuffer);

        // IE9 doesn't fire the oninput event when backspace or delete is pressed. This causes the highlighting
        // to stay on the screen whenever backspace is pressed after a highlighed word. This is simply a hack
        // to force updateValues() to fire when backspace/delete is pressed in IE9.
        if (navigator.userAgent.indexOf("MSIE 9") > -1) {
          T.defer(updateValues);
        }

        return;
      }

      if (e.keyCode == KEY.BACKSPACE) {
        inputBuffer = inputBuffer.slice(0, -1 + inputBuffer.length); // Can't use splice, not available in IE
        return;
      }

      if (!elmAutocompleteList.is(':visible')) {
        return true;
      }

      switch (e.keyCode) {
        case KEY.UP:
        case KEY.DOWN:
          var elmCurrentAutoCompleteItem = null;
          if (e.keyCode == KEY.DOWN) {
            if (elmActiveAutoCompleteItem && elmActiveAutoCompleteItem.length) {
              elmCurrentAutoCompleteItem = elmActiveAutoCompleteItem.next();
            } else {
              elmCurrentAutoCompleteItem = elmAutocompleteList.find('li').first();
            }
          } else {
            elmCurrentAutoCompleteItem = $(elmActiveAutoCompleteItem).prev();
          }

          if (elmCurrentAutoCompleteItem.length) {
            selectAutoCompleteItem(elmCurrentAutoCompleteItem);
          }

          return false;

        case KEY.RETURN:
        case KEY.TAB:
          if (elmActiveAutoCompleteItem && elmActiveAutoCompleteItem.length) {
            elmActiveAutoCompleteItem.trigger('mousedown');
            return false;
          }

          break;
      }

      return true;
    }

    function hideAutoComplete() {
      elmActiveAutoCompleteItem = null;
      elmAutocompleteList.empty().hide();
    }

    function selectAutoCompleteItem(elmItem) {
      elmItem.addClass(settings.classes.autoCompleteItemActive);
      elmItem.siblings().removeClass(settings.classes.autoCompleteItemActive);

      elmActiveAutoCompleteItem = elmItem;
    }

    function populateDropdown(query, results) {
      elmAutocompleteList.show();

      // Filter items that has already been mentioned
      var mentionValues = $.map(mentionsCollection, function(o) { return o["id"]; });
      results = T.reject(results, function (item) {
        return T.include(mentionValues, item.id);
      });

      if (!results.length) {
        hideAutoComplete();
        return;
      }

      elmAutocompleteList.empty();
      var elmDropDownList = $("<ul>").addClass(settings.autoCompleteUL).appendTo(elmAutocompleteList).hide();

      $.each(results, function (index, item) {
        var itemUid = T.uniqueId('mention_');

        autocompleteItemCollection[itemUid] = $.extend({}, item, {value: item.name});
		
		var elmListItem = $(makeReplace(settings.templates.autocompleteListItem, {
          'id'      : utils.htmlEncode(item.id),
          'display' : utils.htmlEncode(item.name),
          'type'    : utils.htmlEncode(item.type),
          'content' : utils.highlightTerm(utils.htmlEncode((item.name)), query)
        })).attr('data-uid', itemUid);
		
        if (index === 0) {
          selectAutoCompleteItem(elmListItem);
        }

        if (settings.showAvatars) {
          var elmIcon;

          if (item.avatar) {
            elmIcon = $(makeReplace(settings.templates.autocompleteListItemAvatar,{ avatar : item.avatar }));
          } else {
            elmIcon = $(makeReplace(settings.templates.autocompleteListItemIcon, { icon : item.icon }));
          } 
          elmIcon.prependTo(elmListItem);
        }
        
        elmListItem = elmListItem.appendTo(elmDropDownList);
      });

      elmAutocompleteList.show();
      elmDropDownList.show();
    }

    function doSearch(query) {
      if (query && query.length && query.length >= settings.minChars) {
        settings.onDataRequest.call(this, 'search', query, function (responseData) {
          populateDropdown(query, responseData);
        });
      }
    }

    function resetInput() {
      elmInputBox.val('');
      mentionsCollection = [];
      updateValues();
    }
	
	// TODO: Fix this so that it works.
	function init_hiliter(textarea) {
		
		var mimics = [
			'paddingTop',
			'paddingRight',
			'paddingBottom',
			'paddingLeft',
			'fontSize',
			'lineHeight',
			'fontFamily',
			'width',
			'fontWeight',
			'border',
			'color',
			//'border-radius',
			'letter-spacing'/*,
			'background'*/
		];

		var i = mimics.length;
		while(i--){
			elmMentionsOverlay.css(mimics[i].toString(),textarea.css(mimics[i].toString()));
		}
		elmMentionsOverlay.css("position", "absolute");
		elmMentionsOverlay.css("top", /*textarea.css("border-top-width")*/0);
		elmMentionsOverlay.css("left", /*textarea.css("border-left-width")*/0);
		elmMentionsOverlay.css("color", "transparent");
		elmMentionsOverlay.css("border-color", "transparent");
		elmMentionsOverlay.css("min-height", textarea.height());
		//elmMentionsOverlay.css("width", (parseInt(textarea.width()) + parseInt(textarea.css("border-left-width")) + parseInt(textarea.css("border-right-width"))*2)+'px');

		textarea.css("overflow", "hidden");
		textarea.css("resize", "none");
		textarea.css("position", "relative");
		//textarea.css("background", "transparent");

		var bgcolor = textarea.css("background");
		textarea.live('focus', function() {
			textarea.css("background", "transparent");
		})
		.live('blur', function() {
			textarea.css("background", bgcolor);
		});
		
		return elmMentionsOverlay;
	}

    // Public methods
    return {
      init : function (domTarget) {

        domInput = domTarget;

        initTextarea();
        initAutocomplete();
        initMentionsOverlay();
        resetInput();

        if( settings.prefillMention ) {
          addMention( settings.prefillMention );
        }

      },

      val : function (callback) {
        if (!T.isFunction(callback)) {
          return;
        }

        var value = mentionsCollection.length ? elmInputBox.data('messageText') : getInputBoxValue();
        callback.call(this, value);
      },

      reset : function () {
        resetInput();
      },

      getMentions : function (callback) {
        if (!T.isFunction(callback)) {
          return;
        }

        callback.call(this, mentionsCollection);
      }
    };
  };

  $.fn.mentionsInput = function (method, settings) {
	
	if(!$.isFunction($.joFunctions)) {
		return this;
	  }
	
	if ($(this).attr('data-mentions-input') == 'true') {
        return this;
      }
	
    var outerArguments = arguments;

    if (typeof method === 'object' || !method) {
      settings = method;
    }

    return this.each(function () {
      var instance = $.data(this, 'mentionsInput') || $.data(this, 'mentionsInput', new MentionsInput(settings));

      if (T.isFunction(instance[method])) {
        return instance[method].apply(this, Array.prototype.slice.call(outerArguments, 1));

      } else if (typeof method === 'object' || !method) {
        return instance.init.call(this, this);

      } else {
        $.error('Method ' + method + ' does not exist');
      }

    });
  };

})(jQuery);