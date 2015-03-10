var wsppclient = (function () {
	
	var wsppclient = function(option) {
		this.option = $.extend(option, { ip: "localhost", port: 8080});
		this.initsocket();
		this.subscription = {recv: {}, discovery: {}, discoveryclose: {}, connect: {}, addsubscription: {}, delsubscription: {}};
	};

	wsppclient.prototype = {
		initsocket: function() {
			this.sock = new WebSocket("ws://" + this.option.ip + ":" + this.option.port);
			this.sock.onopen = (this.option.onopen && typeof this.option.onopen === "function") ? this.option.onopen : this.onopen.bind(this);
			this.sock.onclose   = (this.option.onclose && typeof this.option.onclose === "function") ? this.option.onclose : this.onclose.bind(this);
			this.sock.onmessage = this.onmessage.bind(this);
			this.sock.onerror   = (this.option.onerror && typeof this.option.onerror === "function") ? this.option.onerror : this.onerror.bind(this); 
		},
		connect: function($data, callback) {
			if($data === undefined) { $data = this.option.connect; }
			if($data === undefined) { throw new Error("probleme de connection"); }

			this.sock.send(JSON.stringify({ 
				methode: 'connect',
				datas: $data
			}));
			
			if(callback !== undefined) {
				this.subscription.connect = callback;
			}
		},
		send: function(subscription, datas) {
			this.sock.send(JSON.stringify({ 
				methode: 'send', 
				subscription: subscription, 
				datas: datas
			}));
		},
		addSubscription: function(subscription, callback) {
			this.sock.send(JSON.stringify({ 
				methode: 'addsubscription', 
				subscription: subscription
			}));
			if(callback !== undefined) {
				this.subscription.recv[subscription] = callback;
			}
		},
		delSubscription: function(subscription, callback) {
			this.sock.send(JSON.stringify({ 
				methode: 'delsubscription', 
				subscription: subscription
			}));
			if(callback !== undefined) {
				this.subscription.addsubscription[subscription] = callback;
			}
		},
		discovery: function(subscription, callbacks) {
			this.sock.send(JSON.stringify({ 
				methode: 'discovery', 
				subscription: subscription
			}));
			if(callbacks !== undefined) {
				this.subscription.discovery[subscription] = callbacks.onopen;
				this.subscription.discoveryclose[subscription] = callbacks.onclose;
			}
		},
		onmessage: function(evt) {
			var result = JSON.parse(evt.data);
			
			if(result.type === undefined) {
				return;
			}
			
			if(result.subscription !== undefined && this.subscription[result.type] &&
					this.subscription[result.type][result.subscription] !== undefined && 
					typeof this.subscription[result.type][result.subscription] === "function") {
				this.subscription[result.type][result.subscription].call(this, result.datas);
			} else if(this.subscription[result.type] && typeof this.subscription[result.type] === "function") {
				this.subscription[result.type].call(this, result.datas);
			}
		},
		onopen : function (evt) { console.log(evt); },
		onclose : function(evt) { console.log(evt); },
		onerror : function(evt) { console.log(evt); }
	};
	
	return wsppclient;
}());