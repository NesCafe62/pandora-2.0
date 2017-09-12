
(function() { // es5-polyfill.js

	if (!Array.prototype.forEach) {

		Array.prototype.forEach = function (callback, thisArg) {
			if (this == null) {
				throw new TypeError('Array.prototype.forEach: this is null or not defined');
			}

			var obj = Object(this);
			var len = obj.length >>> 0;
			if (typeof callback !== 'function') {
				throw new TypeError('Array.prototype.forEach: callback is not a function');
			}
			var T;
			if (arguments.length > 1) {
				T = thisArg;
			}

			var k = -1;
			while (++k < len) {
				if (k in obj) {
					callback.call(T, obj[k], k, obj);
				}
			}
		};

	}

	if (!Array.isArray) {
		Array.isArray = function(arg) {
			return Object.prototype.toString.call(arg) === '[object Array]';
		};
	}

	Number.isInteger = Number.isInteger || function(value) {
		return ( typeof value === 'number' && isFinite(value) && !(value % 1) );
	};

})();

(function() { // core.js

	var core = {};

	window.core = core;

})();

(function() { // lib.utils.js

	var utils = {};

	/* utils.toJson = function(obj) {
		return JSON.stringify(obj).replace(/"/g,"'");
	};

	utils.fromJson = function(json) {
		return JSON.parse(json.replace(/'/g,'"'));
	}; */

	utils.cutString = function(str, length) {
		if (str.length > length) {
			var cutStr  = str.substr(0, length-3);
			return cutStr + '...'; // Math.min(cutStr.length, cutStr.lastIndexOf(" "))) + '...';
		} else {
			return str;
		}
	};

	utils.dateFormat = function(date, format) {
		var replaces = {
			'Y': date.getFullYear(),
			'm': date.getMonth(),
			'd': date.getDay(),
			'H': date.getHours(),
			'i': date.getMinutes(),
			's': date.getSeconds()
		};
		for (var pattern in replaces) {
			format = format.replace(new RegExp(pattern,'g'),replaces[pattern]);
		}
		return format;
	};

	utils.sessionId = function() {
		var match = document.cookie.match(RegExp('PHPSESSID=([^;]+)','i'));
		return (match[1]) ? match[1] : false;
	};

	window.utils = utils;

})();

(function() { // lib.callbacks.js

	var Callbacks = function(funcs) {
		if (funcs instanceof Callbacks) {
			return funcs;
		}

		this.hasFuncs = false;

		this.callbacks = [];

		this.obj = {};

		this.bind = function(obj) {
			this.obj = obj;
			return this;
		};

		this.call = function() {
			var self = this.obj;
			var args = arguments;
			this.callbacks.forEach( function(callback) {
				callback.apply(self, args);
			});
		};

		this.add = function(funcs) {
			if (typeof funcs === 'function') {
				this.callbacks.push(funcs);
				this.hasFuncs = true;
				return this;
			} else if (funcs.callbacks !== undefined) {
				funcs = funcs.callbacks;
			} else if (!Array.isArray(funcs)) {
				console.log('Error [Callbacks.add]: argument must be a function, Callbacks or array');
				return this;
			}

			var self = this;
			funcs.forEach(function(func) {
				if (typeof func === 'function') {
					self.callbacks.push(func);
					self.hasFuncs = true;
				}
			});
			return this;
		};

		this.clear = function() {
			this.callbacks = [];
			this.hasFuncs = false;
			return this;
		};

	};

	window.Callbacks = Callbacks;

})();


(function() { // lib.socket.js

	var Socket = function(url, keepAlive) {
		var self = this;

		this.log = function(message) {
			console.log(utils.dateFormat(new Date(), 'H:i:s')+' '+message);
		};

		var failedAttempts = 0;

		function createConnection(url) {
			var connection = new WebSocket(url);

			connection.onopen = function(e) {
				self.log('socket open');
				if (messagesQueue.length) {
					messagesQueue.forEach( function(msg) {
						self.connection.send(msg);
					});
					messagesQueue = [];
				}
			};

			connection.onclose = function(e) {
				if (e.wasClean) {
					self.keepAlive = false;
					self.log('socket close: code '+e.code);
				} else {
					failedAttempts++;
					self.log('socket disconnect: code '+e.code);
					if (self.keepAlive && failedAttempts < 2) {
						clearInterval(this.keepAliveTimer);
						refreshKeepAlive();
						//self.reconnect();
					}
				}
			};

			connection.onmessage = function(e) {
				var data = JSON.parse(e.data);
				// self.log('socket received: "'+utils.cutString(JSON.stringify(data.data), 100)+'"');
				console.log(utils.dateFormat(new Date(), 'H:i:s')+' socket received:', data.data);
				self.onMessage.call(data);
			};

			connection.onerror = function(e) {
				self.log('socket error: '+e.message);
			};

			return connection;
		}

		this.connection = createConnection(url);

		// var lastConnectTime = null;

		this.reconnect = function() {
			if (self.connection.readyState === WebSocket.CLOSED) {
				/* var currentTime = Date.getTime();
				if (lastConnectTime === null || currentTime > lastConnectTime + 2000) {
					lastConnectTime = currentTime; */
				self.connection = createConnection(url);
				// }
			}
		};

		var messagesQueue = [];

		this.keepAlive = (keepAlive !== undefined) ? keepAlive : true;

		this.onMessage = new Callbacks();

		this.messagesQueue = messagesQueue;

		function refreshKeepAlive() {
			this.keepAliveTimer = setInterval( function() {
				if (!self.keepAlive) {
					clearInterval(this.keepAliveTimer);
					return;
				}
				self.reconnect();
			}, 5000);
		}

		if (this.keepAlive) {
			refreshKeepAlive();
		}

		this.send = function(msg) {
			if (self.connection.readyState === WebSocket.OPEN) {
				self.log('socket sending: "'+msg+'"');
				self.connection.send(msg);
			} else {
				self.reconnect();
				messagesQueue.push(msg);
			}
		};
	};

	var SocketChannel = function(socket, channel) {
		this.on = function(event, callback) {
			if (typeof callback !== 'function') {
				console.log('Error [SocketChannel.on]: callback is not a function {channel: "'+channel+'", event: "'+event+'"}');
				return false;
			}
			socket.onMessage.add( function(msg) {
				if (msg.channel == channel && msg.event == event) {
					callback(msg.data);
				}
			});
			return true;
		};

		this.send = function(event, data) {
			socket.send(JSON.stringify({
				sessionId: utils.sessionId(),
				channel: channel,
				event: event,
				data: data
			}));
		};
	};

	Socket.prototype.channel = function(channel) {
		return new SocketChannel(this, channel);
	};

	core.socket = function(url, keepAlive) {
		return new Socket(url, keepAlive);
	};

})();



// window.socket = core.socket('ws://api.domain.com:80', true); // << set your domain instead of api.domain.com:80

// var debugSocket = socket.channel('debug');

var chatSocket = socket.channel('chat.room124');

/* chatSocket.on('updates.message', function(data) {
	console.log(data);
}); */
