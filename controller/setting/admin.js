angular.module('App').controller('AdminController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $route, $timeout, request) {

	var self    = $scope;
	var root    = $rootScope;
    self.q      = '';
    self.cur_id = root.getSessionUid();

	root.closeAndDisableSearch();
	root.toolbar_menu = { title: 'Add Admin' };
	$rootScope.pagetitle = 'Setting : Administrator';

    self.$on('barAction', function (event, data) {
        self.manageAdministrator(event, null);
    });

    self.loadPages = function () {
        $_q = self.q ? self.q : '';
        request.getAllUserPanelCount($_q).then(function (resp) {
            self.paging.total = Math.ceil(resp.data / self.paging.limit);
            self.paging.modulo_item = resp.data % self.paging.limit;
        });
        $limit = self.paging.limit;
        $current = (self.paging.current * self.paging.limit) - self.paging.limit + 1;
        if (self.paging.current == self.paging.total && self.paging.modulo_item > 0) {
            self.limit = self.paging.modulo_item;
        }
        request.getAllUserPanelByPage($current, $limit, $_q).then(function (resp) {
            self.user_panel = resp.data;
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

    self.manageAdministrator = function (ev, user_id) {
        $mdDialog.show({
            controller: AdminAddControllerDialog,
            templateUrl: 'view/setting/admin_add.html',
            parent: angular.element(document.body),
            targetEvent: ev,
            clickOutsideToClose: false,
            user_id: user_id
        })
    };

    self.deleteUserPanel = function(ev, up) {
        var confirm = $mdDialog.confirm().title('Delete Confirmation');
        confirm.content('Are you sure want to delete Administrator : '+up.name+' ?');
        confirm.targetEvent(ev).ok('OK').cancel('CANCEL');

        $mdDialog.show(confirm).then(function() {
            request.deleteOneUserPanel(up.id).then(function(res){
                if(res.status == 'success'){
                    root.showConfirmDialogSimple('', 'Delete Administrator '+up.name+' SUCCESS', function(){
                        window.location.reload();
                    });
                }else{
                    var msg = res.msg != null ? res.msg : 'Opps , <b>Failed Delete</b> Administrator '+up.name;
                    root.showInfoDialogSimple('', msg);
                }
            });
        });

    };
	
});


function AdminAddControllerDialog($rootScope, $scope, $mdDialog, $timeout, request, $mdToast, user_id) {
    var self 		= $scope;
    var root 		= $rootScope;
    var is_new 		= ( user_id == null );
    var original 	= null;
    var now         = new Date().getTime();
    self.user_panel = null;
    self.roles		= ['ADMIN', 'WRITER'];

    self.dialog_title = (is_new) ? 'Add Administrator' : 'Edit Administrator';
    self.submit_loading = false;
    self.hide 	= function () { $mdDialog.hide(); };
    self.cancel = function () { $mdDialog.cancel(); };

    if (is_new) {
        original = { name: null, username: null, email: null, password: null, role: 'WRITER',
            created_at: now, last_update: now };
        self.user_panel = angular.copy(original);
    } else {
        request.getOneUserPanel(user_id).then(function (data) {
            self.user_panel = data.data;
            self.user_panel.password = '*****';
            original = angular.copy(self.userdata);
        });
    }


    self.isClean = function () { return angular.equals(original, self.user_panel);};

    self.isPasswordMatch = function () {
        if (self.re_password == null || self.re_password == '') {
            return true;
        } else {
            return (self.re_password == self.user_panel.password);
        }
    };

    self.submit = function (user_panel) {
        now = new Date().getTime();
        self.submit_loading = true;
        if (is_new) {
            if (user_panel.password === '*****') {
                user_panel.password = "";
                self.submit_loading = false;
                return;
            }
            if (self.re_password != user_panel.password) {
                self.submit_loading = false;
                return;
            }
            user_panel.id = null;
            request.insertOneUserPanel(user_panel).then(function (resp) {
                self.afterSubmit(resp);
            });
        } else {
            user_panel.last_update = now;
            request.updateOneUserPanel(user_panel.id, user_panel).then(function (resp) {
                if (resp.status == 'success' && user_panel.id == root.getSessionUid()) {
                    root.saveCookies(
                    	resp.data.user_panel.id, resp.data.user_panel.name, resp.data.user_panel.email,
						resp.data.user_panel.password, resp.data.user_panel.role
					);
                }
                self.afterSubmit(resp);
            });
		}
    };

    self.afterSubmit = function (resp) {
        $timeout(function () { // give delay for good UI
            self.submit_loading = false;
            if(resp.status == 'success'){
                root.showConfirmDialogSimple('', resp.msg, function(){
                    window.location.reload();
                });
            }else{
                root.showInfoDialogSimple('', resp.msg);
            }
        }, 1000);
    };

}

