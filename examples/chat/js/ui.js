/**
 * PHP WebSocket library
 *
 * This file implements the UI class that manages the layout
 */

/**
 * @namespace
 */
var Chat = Chat || {};

/**
 * The Ui class
 *
 * This class manages the layout and other ui aspects
 *
 * @class
 */
Chat.Ui = function() {

	// vars
	var chatEl      = $('#chat-area'),
		tagList     = $('#list'),
		inputArea   = $('#input-area'),
		inputEl     = $('#message'),
		sayBtn      = $('#say'),
		chatEl      = $('#chat-area'),
		headerEl    = $('.page-header'),
		windowEl    = $(window),
		chatDiff    = chatEl.outerHeight(true) - chatEl.height() + 20,
		listDiff    = tagList.outerHeight(true) - tagList.height() + 20,
		inputDiff   = (inputEl.outerWidth(true) - inputEl.width())
					+ sayBtn.outerWidth(true),
		connected   = false,
		dlgEl       = $('#details'),
		saveBtn     = dlgEl.find('a.save'),
		closeBtn    = dlgEl.find('a.disconnect'),
		getChatEl, getListEl, getDlgEl, update, showDetails, setConnected;

	/**
	 * Update the UI
	 */
	update = function() {
		var h = windowEl.height() - headerEl.outerHeight(true);
		chatEl.height(h - chatDiff - inputArea.outerHeight(true));
		tagList.height(h - listDiff);
		if (!Modernizr.flexbox) {
			inputEl.width(inputArea.width() - inputDiff);
		}
	};

	/**
	 * Get the chat area element
	 *
	 * @return {jQuery}
	 * @method
	 */
	getChatEl = function() {
		return chatEl;
	};

	/**
	 * Get the avatar list element
	 *
	 * @return {jQuery}
	 * @method
	 */
	getListEl = function() {
		return tagList;
	};

	/**
	 * Get dialog element
	 *
	 * @return {jQuery}
	 * @method
	 */
	getDlgEl = function() {
		return dlgEl;
	};

	/**
	 * Show details dialog
	 */
	showDetails = function() {
		dlgEl.modal({
			show:       true,
			backdrop:   true
		});
		setTimeout(function() {
			dlgEl.find('#nickname').focus();
		}, 250);
	};

	/**
	 * Set connection status
	 *
	 * @param {Bool} status
	 */
	setConnected = function(status) {
		connected = status;
		if (connected) {
			saveBtn.text('Change');
			closeBtn.show();
		} else {
			saveBtn.text('Connect');
			closeBtn.hide();
		}
	};

	// avatar selector
	dlgEl.find('.avatar-select li').click(function(){
		var li = $(this),
			ul = li.parent();
		ul.find('.active').removeClass('active');
		li.addClass('active');
	});

	// init
	windowEl.bind('resize', update);
	update();
	setConnected(false);

	// public interface
	return {
		getChatEl:      getChatEl,
		getListEl:      getListEl,
		showDetails:    showDetails,
		setConnected:   setConnected,
		getDlgEl:       getDlgEl
	};
};