var onReady = {

	controllerUrl:'controller/controller.php',

	init:function(){
		var me = this,
			urlField = $('input[urlholder]');
		console.log("urlField--->",urlField);
		$('button[type="submit"]').click(function(){
			me.sendDownloadUrl();
			me.showDownloadBar($('button[type="submit"]'));
			return false;
		});
	},
	sendDownloadUrl:function(){
		$.ajax({
		  url: me.controllerUrl,
		  data: {
		  	url:escape(urlField.val()),
		  },
		  success: function(){
		  	console.log("success--->",arguments);
		  },
		});
	},
	showDownloadBar:function(){

	}
};
