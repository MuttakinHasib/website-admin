angular.module('App').controller('NotifHistoryController', function ($rootScope, $scope, $http, $mdToast, $cookies, $mdDialog, $route, request) {
	var self = $scope;
	var root = $rootScope;

	root.pagetitle = 'Notification : History';
	self.loading = true;
	root.search_enable = true;
	root.toolbar_menu = { title: 'Send' };
	
	// receiver barAction from rootScope
	self.$on('barAction', function (event, data) {
		self.sendNotification(event, null);
	});
	
	// receiver submitSearch from rootScope
	self.$on('submitSearch', function (event, data) {
		self.q = data;
		self.loadPages();
	});

	self.loadPages = function() {
		$_q = self.q ? self.q : '';
		request.getAllNotifHistoryCount($_q).then(function (resp) {
			self.paging.total = Math.ceil(resp.data / self.paging.limit);
			self.paging.modulo_item = resp.data % self.paging.limit;
		});
		$limit = self.paging.limit;
		$current = (self.paging.current * self.paging.limit) - self.paging.limit + 1;
		if (self.paging.current == self.paging.total && self.paging.modulo_item > 0) {
			self.limit = self.paging.modulo_item;
		}
		request.getAllNotifHistoryByPage($current, $limit, $_q).then(function (resp) {
			self.history = resp.data;
			self.loading = false;
		});
	}
	//pagination property
	self.paging = {
		total: 0, // total whole item
		current: 1, // start page
		step: 3, // count number display
		limit: 20, // max item per page
		modulo_item: 0,
		onPageChanged: self.loadPages,
	};

	self.sendNotification = function (ev, obj) {
		$mdDialog.show({
			controller: SendControllerDialog,
			templateUrl: 'view/notification/send.html',
			parent: angular.element(document.body),
			targetEvent: ev,
			clickOutsideToClose: false,
			obj: obj
		})
	};

});

function SendControllerDialog($rootScope, $scope, $mdDialog, request, $mdToast, $route, $timeout, obj) {
	var self = $scope;
	var root = $rootScope;

	self.dialog_title = 'Send Notification ';
	self.submit_loading = false;
	self.hide 	= function () { $mdDialog.hide(); };
	self.cancel = function () { $mdDialog.cancel(); };
	self.showResult = false;
    self.body = root.getNotificationBody('NORMAL', null, null, null, null);
	if(obj != null) {
		self.dialog_title = self.dialog_title + ' to : '+obj.device_name;
        self.body.reg_id = obj.regid;
	}
	self.submit = function(b) {
		self.submit_loading = true;
		self.showResult = false;
		root.requestPostNotification(b, function(resp){
            self.result = { status:null, msg:null };
            if( resp != null && resp != '' ){
                self.result = resp;
            } else {
                self.result.status = 'failed';
                self.result.msg = 'Failed send Notification';
            }
            self.show_result = true;
            self.submit_loading = false;
		});

	};
}
