angular.module('App').controller('CommentController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $cookies, request) {
	var self = $scope;
	var root = $rootScope;

    root.search_enable = true;
	root.toolbar_menu = null;
	root.pagetitle = 'Comment';

    // receiver submitSearch from rootScope
    self.$on('submitSearch', function (event, data) {
        self.q = data;
        self.loadPages();
    });
	
	self.loadPages = function () {
		$_q = self.q ? self.q : '';
        root.setStatePageCat(self.paging.current);
		request.getAllNewsCommentCount($_q).then(function (resp) {
			self.paging.total = Math.ceil(resp.data / self.paging.limit);
			self.paging.modulo_item = resp.data % self.paging.limit;
		});
		$limit = self.paging.limit;
		$current = (self.paging.current * self.paging.limit) - self.paging.limit + 1;
		if (self.paging.current == self.paging.total && self.paging.modulo_item > 0) {
			self.limit = self.paging.modulo_item;
		}
		request.getAllNewsCommentByPage($current, $limit, $_q).then(function (resp) {
			self.comment = resp.data;
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
		onPageChanged: self.loadPages
	};

	self.removeComment = function(ev, c) {
		var confirm = $mdDialog.confirm().title('Remove Confirmation');
			confirm.content('Are you sure want to remove Comment from database ?');
			confirm.targetEvent(ev).ok('OK').cancel('CANCEL');

		$mdDialog.show(confirm).then(function() {
			request.deleteOneNewsComment(c.id).then(function(res){
				if(res.status == 'success'){
				    root.showConfirmDialogSimple('', 'Remove Comment Success !', function(){
				        window.location.reload();
				    });
				}else{
                    var msg = res.msg != null ? res.msg : 'Opps , Failed Remove Comment';
                    root.showInfoDialogSimple('', msg);
				}
			});
		});

	};

    self.viewUserApp = function(ev, user_id) {
        $mdDialog.show({
            controller : ViewUserAppCtl,
            parent: angular.element(document.body), targetEvent: ev, clickOutsideToClose: true, user_id: user_id,
            template:
            '<md-dialog ng-cloak layout="column">' +
            '  <md-dialog-content class="md-dialog-content">' +
            '   <p class="md-title">User App Details</p> ' +
			'	<img class="md-center" style="margin: auto; max-width: 100%;max-height: 150px;" ng-src="{{image_url}}">'+
			'	<div layout="column">' +
			'		<span class="md-subhead">Name  : {{user.name}}</span>' +
			'		<span class="md-subhead">Email : {{user.email}}</span>' +
			'	</div> ' +
            '  </md-dialog-content>' +
            '	<div layout="row" class="md-actions"> <span flex></span>' +
            '       <md-button ng-click="cancel()" class="md-primary">OK</md-button>' +
            '   </div>' +
            '</md-dialog>'
        });
        function ViewUserAppCtl($scope, $mdDialog, $mdToast, user_id) {
            $scope.cancel = function() { $mdDialog.cancel(); };
            request.getOneUserApp(user_id).then(function(resp){
                $scope.user = angular.copy(resp.data);
                $scope.image_url = 'uploads/user/' + $scope.user.image;
            });
        }
    };

	self.changeCommentStatus = function(ev, c, status) {
		var title = "";
		var content = "";

		if(status == 'SHOW'){
            title = 'Show Comment';
            content = 'Comment will appear in list and the user will be able to see it.';
		} else {
            title = 'Hide Comment';
            content = 'Comment will appear in list but the user will not be able to see it.';
		}

		var confirm = $mdDialog.confirm().title(title);
			confirm.content(content);
			confirm.targetEvent(ev).ok('YES, SURE!').cancel('CANCEL');

		$mdDialog.show(confirm).then(function() {
			request.updateStatusNewsComment(c.id, status).then(function(res){
				if(res.status == 'success'){
				    root.showConfirmDialogSimple('', title + ' Success !', function(){
				        window.location.reload();
				    });
				}else{
                    var msg = res.msg != null ? res.msg : 'Opps , <b>Failed</b> ' + title;
                    root.showInfoDialogSimple('', msg);
				}
			});
		});

	};

});
