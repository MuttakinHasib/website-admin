angular.module('App').controller('NotifDeviceController', function ($rootScope, $scope, $http, $mdToast, $cookies, $mdDialog, $route, request) {
	var self = $scope;
	var root = $rootScope;

	root.pagetitle = 'Notification : Device';
	self.loading = true;
	root.search_enable = true;
	
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
		request.getAllNotifDeviceCount($_q).then(function (resp) {
			self.paging.total = Math.ceil(resp.data / self.paging.limit);
			self.paging.modulo_item = resp.data % self.paging.limit;
		});
		$limit = self.paging.limit;
		$current = (self.paging.current * self.paging.limit) - self.paging.limit + 1;
		if (self.paging.current == self.paging.total && self.paging.modulo_item > 0) {
			self.limit = self.paging.modulo_item;
		}
		request.getAllNotifDeviceByPage($current, $limit, $_q).then(function (resp) {
			self.notif_device = resp.data;
			self.loading = false;
		});
	};

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