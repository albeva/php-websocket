/**
 * PHP WebSocket library
 *
 * This file implements the Client class for the chat sample
 */

/**
 * @namespace
 */
var Chat = Chat || {};

/**
 * The client class
 *
 * The client represents the online user and manages its
 * avatar, name and messages.
 *
 * @param {Object} options
 * @class
 */
Chat.Client = function(options) {
	// vars
	var name    = options.name,
		avatar  = options.avatar,
		id      = options.id,
		myself  = options.myself,
		ui      = Chat.getUi(),
		online  = true,
		tagEl   = $('<div class="user avatar-' + avatar + '"/>'),
		msgSel  = myself ? 'me' : 'id-' + id,
		chatEl  = ui.getChatEl(),
		tagList = ui.getListEl(),
		update, say, connect, disconnect, getInfo, getId;

	/**
	 * Update user information
	 *
	 * @param {Object} options
	 * @method
	 */
	update = function(options) {
		var oldAvatar = avatar;
		name = options.name;
		avatar = options.avatar;
		// update the avatar
		tagEl.removeClass('avatar-' + oldAvatar).addClass('avatar-' + avatar);
		// update the name
		if (online) tagEl.text(name);
		// update name in the chat area
		chatEl.find('.' + msgSel + ' em').text(name);
	};

	/**
	 * Say the message
	 *
	 * @param {String} message
	 * @method
	 */
	say = function(message) {
		var msg = $('<p class="' + msgSel + '"/>')
			.text(message)
			.prepend($('<em />').text(name));
		chatEl.append(msg);
		chatEl.stop().animate({
			scrollTop: chatEl.prop("scrollHeight") - chatEl.height()
		}, 200);
	};

	/**
	 * Connect the user (only for own tag)
	 *
	 * @param {int} id
	 * @method
	 */
	connect = function(userId) {
		if (!myself) return;
		tagEl.removeClass('offline').addClass('online').text(name);
		id = userId;
		online = true;
	};

	/**
	 * Disconnect the user
	 * @method
	 */
	disconnect = function() {
		if (myself) {
			tagEl.removeClass('online').addClass('offline').text('Connect');
			online = false;
		} else {
			tagEl.remove();
		}
		chatEl.find('.' + msgSel).addClass('offline');
	};

	/**
	 * Get user info
	 *
	 * @method
	 * @return {Object}
	 */
	getInfo = function() {
		return {
			name : name,
			avatar : avatar
		};
	};

	/**
	 * Get client id
	 *
	 * @method
	 * @return {int}
	 */
	getId = function() {
		return id;
	};

	// define user tag
	if (myself) {
		tagEl.text('Connect').addClass('offline');
		tagEl.click(ui.showDetails);
		online = false;
	} else {
		tagEl.text(name).addClass('client');
	}

	// show it in the list
	tagList.append(tagEl);

	// public interface
	return {
		update:     update,
		say:        say,
		disconnect: disconnect,
		connect:    connect,
		getInfo:    getInfo,
		getId:      getId
	};
};