var wsppclient = (function () {
	
	var wsppclient = function(option) {
		this.option = $.extend(option, { ip: "localhost", port: 8080});
		this.connect();
		this.subscription = [];
	};
	
	wsppclient.prototype = {
		connect: function() {
			this.sock = new WebSocket("ws://" + this.option.ip + ":" + this.option.port);
			this.sock.onopen    = this.onopen.bind(this);
			this.sock.onclose   = this.onclose.bind(this);
			this.sock.onmessage = this.onmessage.bind(this);
			this.sock.onerror   = this.onerror.bind(this);
		},
		sendDatas: function(subscription, datas) {
	    	this.sock.send(JSON.stringify({ 
				methode: 'sendmessage', 
				subscription: subscription, 
				datas: datas
			}));
		},
		addSubscription: function(subscription, callback) {
			this.sock.send(JSON.stringify({ 
				methode: 'addsubscription', 
				subscription: subscription
			}));
			this.subscription[subscription] = callback;
		},
		discovery: function(subscription) {
			this.sock.send(JSON.stringify({ 
				methode: 'discovery', 
				subscription: subscription
			}));
		},
		onmessage: function(evt) {
			console.log(this);
			var result = JSON.parse(evt.data);
			this.subscription[result.subscription].call(this, result.datas);
		},
		onopen : function (evt) { console.log(evt); },
		onclose : function(evt) { console.log(evt); },
		onerror : function(evt) { console.log(evt); }
	};
	
	
	
	return wsppclient;
}());