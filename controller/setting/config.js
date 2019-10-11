angular.module('App').controller('ConfigController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $route, $timeout, request) {

	var self = $scope;
	var root = $rootScope;

	root.closeAndDisableSearch();
	root.toolbar_menu = null;
	$rootScope.pagetitle = 'Setting : Config';
	
	/* Script controller for : Application Setting*/
	
	var original_conf = null;
	self.config = [];
    self.tags 	= [];
	self.conf_featured_news = null;
	self.conf_featured_topic = null;

	request.getAllConfig().then(function (resp) {
		self.config = resp.data;
		original_conf = angular.copy(self.config);
		self.conf_featured_news = root.findValue(self.config, 'FEATURED_NEWS');
		self.conf_featured_topic = root.findValue(self.config, 'FEATURED_TOPIC');
	});
	
	self.setValue = function (code, value) {
		for (var i = 0; i < self.config.length; ++i) {
			var obj = self.config[i];
			if(obj.code == code) {
				self.config[i].value = value;
			}
		}
	};

	/* checker when all data ready to submit */
	self.isReadySubmitConf = function () {
		self.setValue('FEATURED_NEWS', self.conf_featured_news);
		self.setValue('FEATURED_TOPIC', self.conf_featured_topic);

		return !angular.equals(original_conf, self.config);
	};
	
	self.submitConf = function() {	
		self.submit_loading_conf = true;
        request.updateAllConfig(self.config).then(function(resp){
            self.submit_loading_conf = false;
            if(resp.status == 'success'){
                root.showConfirmDialogSimple('', resp.msg, function(){
                    window.location.reload();
                });
            }else{
                root.showInfoDialogSimple('', resp.msg);
            }
        });
	};
	
});
