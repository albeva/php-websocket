//
// PHP WebSocket library chat example
//
$(function(){
	// vars
	var host        = 'ws://173.203.111.216:12345',
		inputArea   = $('#input-area'),
		inputEl     = $('#message'),
		sayBtn      = $('#say'),
		chatEl      = $('#chat-area'),
		listEl      = $('#list'),
		headerEl    = $('.page-header'),
		windowEl    = $(window),
		chatDiff    = chatEl.outerHeight(true) - chatEl.height(),
		listDiff    = listEl.outerHeight(true) - listEl.height(),
		socket      = null,
		connected   = false,
		dlgEl       = $('#details'),
		userTagEl   = listEl.find('.me'),
		avatarId    = 1,
		saveBtn     = dlgEl.find('a.save'),
		closeBtn    = dlgEl.find('a.disconnect'),
		myId        = null,
		myName      = '',
		clients     = {};

	// layout the elements
	var setHeight = function() {
		var h = windowEl.height() - headerEl.outerHeight(true);
		chatEl.height(h - chatDiff - inputArea.outerHeight(true) - 20);
		listEl.height(h - listDiff - 20);
	};
	windowEl.bind('resize', setHeight);
	setHeight();

	// avatar selector
	$('.avatar-select li').click(function(){
		var li = $(this),
			ul = li.parent();
		ul.find('.active').removeClass('active');
		li.addClass('active');
	});

	dlgEl.bind('show', function() {
		saveBtn.text(
			connected ? 'Change' : 'Connect'
		);
		closeBtn[connected ? 'show' : 'hide']();
	});

	// log data
	var log = function(log) {
		console.info(log);
	};

	// send the message
	var send = function(message) {
		if (connected) {
			try {
				var json = JSON.stringify(message);
				socket.send(json);
			} catch(ex) {
				log(ex);
			}
		}
	};


	var say = function() {
		var msg = inputEl.val();
		inputEl.val('');
		inputEl.focus();
		if (msg) {
			send({
				action: 'message',
				message: msg
			});
		}
	};

	// add client
	var addClient = function(user) {
		var id   = user.id,
			name = user.name,
			el   = $('<div class="user other id-' + id +
				' avatar-' + user.avatar + '" />');
		el.text(user.name);
		el.appendTo(listEl);
		clients[id] = name;
	};

	// remove client
	var removeClient = function(user) {
		var id = user.id,
			el = listEl.find('.id-' + user.id);
		if (!el.length) return;

		el.remove();

		delete clients[id];
	};

	// update client
	var updateClient = function(user) {
		var id = user.id,
			el = listEl.find('.id-' + user.id);
		if (!el.length) return;

		el[0].className = 'user other id-' + id + ' avatar-' + user.avatar;
		el.text(user.name);

		chatEl.find('.id-' + id + ' em').text(user.name);

		clients[id] = user.name;
	};

	// connect to the server
	var connect = function(options) {
		var WsClass = window.MozWebSocket || window.WebSocket;
		socket = new WsClass(host);
		socket.onopen = function(msg){
			userTagEl.removeClass('disconnected')
					 .addClass('connected')
					 .text(options.name);
			connected = true;
			inputEl.focus();
		};
		socket.onmessage = function(msg){
			var data = $.parseJSON(msg.data);
			console.info(data);
			if (data.action == 'message') {
				var name = data.id == myId ? myName : clients[data.id];
				chatEl.append(
					$('<p class="id-' + data.id +  '">').text(data.message).
					prepend('<em>' + name + '</em> ')
				);
				chatEl.scroll();
				chatEl.animate({scrollTop: chatEl.attr("scrollHeight") }, 500);
			} else if (data.action == 'notify') {
				myId = data.id;
				send(options);
				if (data.clients) {
					for(var i = 0; i < data.clients.length; i++) {
						addClient(data.clients[i]);
					}
				}
			} else if (data.action == 'update') {
				if (data.id == myId) return;
				updateClient(data);
			} else if (data.action == 'connect') {
				if (data.id == myId) return;
				addClient(data);
			} else if (data.action == 'disconnect') {
				if (data.id == myId) return;
				removeClient(data);
			}
		};
		socket.onclose = function(msg){
			userTagEl.removeClass('connected')
					 .addClass('disconnected')
					 .text('Connect');
			connected = false;
			socket = null;
			listEl.find('.other').remove();
		};
	};

	// handle settings
	var setSettings = function() {
		var name = dlgEl.find('#nickname').val(),
			avatar = dlgEl.find('.avatar-select .active').index() + 1;

		if (!name.length) {
			dlgEl.find('#nickname').parents('.clearfix').addClass('error');
			return;
		} else {
			dlgEl.find('#nickname').parents('.clearfix').removeClass('error');
		}
		myName = name;

		if (avatarId != avatar) {
			userTagEl.removeClass('avatar-' + avatarId).addClass('avatar-' + avatar);
			avatarId = avatar;
		}

		// hide
		dlgEl.modal('hide');

		// connect or change
		if (connected) {
			userTagEl.text(name);
			send({
				action: 'update',
				name:   name,
				avatar: avatar
			});
		} else {
			connect({
				action: 'connect',
				name:   name,
				avatar: avatar
			})
		}

		return false;
	};

	// catch the click
	saveBtn.click(setSettings);

	// disconnect
	closeBtn.click(function(){
		if (socket) socket.close();
		dlgEl.modal('hide');
	});

	inputEl.keypress(function(e){
		if (e.which == 13) {
			e.preventDefault();
			say();
		}
	});
	sayBtn.click(say);

	// show connect
	dlgEl.modal({
		show : true,
		backdrop: true
	});
});
