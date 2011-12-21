/**
 * PHP WebSocket library
 *
 * This file implements the main logic of the chat example
 */

/**
 * @namespace
 */
var Chat = Chat || {};

/**
 * The main function.
 */
$(function() {

	// ui manager
	var ui          = new Chat.Ui(),
		clients     = {},
		connected   = false,
		socket      = null,
		host        = window.socket_host,
		WSocket     = window.MozWebSocket || window.WebSocket,
		myself      = null,
		setSettings, send, connect, close, onOpen, onMessage, onClose, say;

	/**
	 * Get Ui object
	 */
	Chat.getUi = function() {
		return ui;
	};

	/**
	 * Set the settings. Callback from popup dialog
	 */
	setSettings = function() {
		// vars
		var dlgEl = ui.getDlgEl(),
			nameEl = dlgEl.find('#nickname'),
			name = nameEl.val(),
			avatar = dlgEl.find('.avatar-select .active').index() + 1;

		// check that name is given
		if (!name.length) {
			nameEl.parents('.clearfix').addClass('error');
			nameEl.focus();
			return false;
		} else {
			nameEl.parents('.clearfix').removeClass('error');
		}

		// the data packet
		var packet = {
			avatar:     avatar,
			name:       name
		};

		// update myself
		myself.update(packet);

		// hide
		dlgEl.modal('hide');

		// connect or change
		if (connected) {
			packet.action = 'update';
			send(packet);
		} else {
			packet.action = 'connect';
			connect(packet);
		}

		return false;
	};

	/**
	 * Send packet to the server
	 *
	 * @param {Object} packet
	 */
	send = function(packet) {
		if (connected) {
			try {
				var json = JSON.stringify(packet);
				socket.send(json);
			} catch(ex) {
			}
		}
	};

	/**
	 * On open socket connection
	 *
	 * @param {Object} msg
	 */
	onOpen = function(msg){
		connected = true;
		ui.setConnected(true);
	};

	/**
	 * On messaged received
	 *
	 * @param {Objetc} msg
	 */
	onMessage = function(msg){
		var data    = $.parseJSON(msg.data),
			id      = data.id;
		if (data.action == 'message') {
			clients[id].say(data.message);
		} else if (data.action == 'nudge') {
			clients[id].say('sent you a nudge', true);
		} else if (data.action == 'notify') {
			clients[id] = myself;
			myself.connect(id);
			var packet = myself.getInfo();
			packet.action = 'connect';
			send(packet);
			if (data.clients) {
				for(var i = 0; i < data.clients.length; i++) {
					var c = data.clients[i];
					clients[c.id] = new Chat.Client(c);
				}
			}
		} else if (data.action == 'update') {
			if (id != myself.getId()) {
				clients[id].update(data);
			}
		} else if (data.action == 'connect') {
			if (id != myself.getId()) {
				clients[id] = new Chat.Client(data);
				clients[id].say("connected", true);
			}
		} else if (data.action == 'disconnect') {
			clients[id].say("disconnected", true);
			clients[id].disconnect();
			delete clients[id];
		}
	};

	/**
	 * On close socket
	 *
	 * @param {Object} msg
	 */
	onClose = function(msg) {
		connected = false;
		ui.setConnected(false);
		for(var k in clients) {
			if (clients.hasOwnProperty(k)) {
				clients[k].disconnect();
			}
		}
		clients = {};
		ui.getListEl().find('.client').remove();
		return false;
	};

	/**
	 * connect with the server
	 *
	 * @param {Object} packet
	 */
	connect = function(packet) {
		socket = new WSocket(host);
		socket.onopen = onOpen;
		socket.onmessage = onMessage;
		socket.onclose = onClose;
	};

	/**
	 * Close the connection
	 */
	close = function() {
		if (socket) socket.close();
		ui.getDlgEl().modal('hide');
	};

	/**
	 * Send the message from the input bot to the server
	 */
	say = function() {
		var msgEl = $('#message'),
			val = msgEl.val();
		msgEl.val('');
		msgEl.focus();
		send({
			action: 'message',
			message: val
		});
		return false;
	};

	// this client.
	myself = new Chat.Client({
		id      : 1,
		avatar  : 1,
		myself  : true
	});

	// show dialog first thing
	ui.getDlgEl().find('a.save').click(setSettings);
	ui.getDlgEl().find('form').submit(setSettings);
	ui.getDlgEl().find('a.disconnect').click(close);
	ui.showDetails();

	// nudge others
	ui.getListEl().on('click', '.client', function(){
		console.info($(this).data('client'));
		send({
			action: 'nudge',
			target: $(this).data('client').getId()
		});
		$(this).data('client').say("was nudged by you", true);
	});

	// send the message
	$('#message').keypress(function(e){
		if (e.which == 13) {
			e.preventDefault();
			say();
		}
	});
	$('#say').click(say);
});
