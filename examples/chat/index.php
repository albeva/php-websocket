<!doctype html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>PHP WebSocket Chat sample</title>
		<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" media="all">
		<link rel="stylesheet" type="text/css" href="css/chat.css" media="all">
		<script type="text/javascript">
			window.WEB_SOCKET_SWF_LOCATION = "web-socket-js/WebSocketMain.swf";
			window.socket_host = "<?= file_get_contents('wshost.tmp') ?>";
		</script>
		<script type="text/javascript" src="web-socket-js/swfobject.js"></script>
		<script type="text/javascript" src="web-socket-js/web_socket.js"></script>
		<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="js/bootstrap-modal.js"></script>
		<script type="text/javascript" src="js/modernizr.custom.81235.js"></script>
		<script type="text/javascript" src="js/client.js"></script>
		<script type="text/javascript" src="js/ui.js"></script>
		<script type="text/javascript" src="js/app.js"></script>
	</head>
	<body>
		<div id="wrapper">
			<div class="page-header">
				<h1>PHP WebSocket Chat sample</h1>
			</div>
			<div id="main">
				<div id="content">
					<div id="chat-area">
					</div>
					<div id="input-area">
						<input type="text" name="message" id="message">
						<button id="say" class="btn primary">Say</button>
					</div>
				</div>
				<div id="sidebar">
					<div id="list"></div>
				</div>
			</div>
		</div>
		<!-- sample modal content -->
		<div id="details" class="modal hide fade">
			<div class="modal-header">
				<a href="#" class="close">&times;</a>
				<h3>Your details</h3>
			</div>
			<div class="modal-body">
				<form action="?">
					<div class="clearfix">
						<label for="nickname">Your Nickname</label>
						<div class="input">
							<input id="nickname" class="xlarge" type="text" maxlength="10" name="nickname">
						</div>
					</div>
					<div class="clearfix">
						<label>Your Avatar</label>
						<div class="input">
							<ul class="avatar-select">
								<li class="avatar-1 active"></li>
								<li class="avatar-2"></li>
								<li class="avatar-3"></li>
								<li class="avatar-4"></li>
								<li class="avatar-5"></li>
								<li class="avatar-6"></li>
								<li class="avatar-7"></li>
								<li class="avatar-8"></li>
							</ul>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<a href="#" class="btn save primary">Save</a>
				<a href="#" class="btn disconnect danger">Disconnect</a>
			</div>
		</div>
	</body>
</html>