angular.module('App').controller('UserController', function ($rootScope, $scope, $mdDialog, request) {
    var self = $scope;
    var root = $rootScope;

    root.search_enable = true;
    root.toolbar_menu = null;
    $rootScope.pagetitle = 'User';

    // receiver submitSearch from rootScope
    self.$on('submitSearch', function (event, data) {
        self.q = data;
        self.loadPages();
    });

    self.loadPages = function () {
        $_q = self.q ? self.q : '';
        request.getAllUserAppCount($_q).then(function (resp) {
            self.paging.total = Math.ceil(resp.data / self.paging.limit);
            self.paging.modulo_item = resp.data % self.paging.limit;
        });
        $limit = self.paging.limit;
        $current = (self.paging.current * self.paging.limit) - self.paging.limit + 1;
        if (self.paging.current == self.paging.total && self.paging.modulo_item > 0) {
            self.limit = self.paging.modulo_item;
        }
        request.getAllUserAppByPage($current, $limit, $_q).then(function (resp) {
            self.user_app = resp.data;
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

    self.detailsDevice = function(ev, notif_device) {
        $mdDialog.show({
            controller : ViewDeviceCtl,
            parent: angular.element(document.body), targetEvent: ev, clickOutsideToClose: true, notif_device: notif_device,
            template:
            '<md-dialog ng-cloak layout="column">' +
            '  <md-dialog-content class="md-dialog-content">' +
            '   <p class="md-title">Notif Device Details</p> ' +
            '	<div layout="column">' +
            '		<span class="md-subhead">Device Name  : {{device.device_name}}</span>' +
            '		<span class="md-subhead">Device ID    : {{device.device_id}}</span>' +
            '		<span class="md-subhead">OS Version   : {{device.os_version}}</span>' +
            '		<span class="md-subhead">App Version  : {{device.app_version}}</span>' +
            '	</div> ' +
            '  </md-dialog-content>' +
            '	<div layout="row" class="md-actions"> <span flex></span>' +
            '       <md-button ng-click="cancel()" class="md-primary">OK</md-button>' +
            '   </div>' +
            '</md-dialog>'
        });
        function ViewDeviceCtl($scope, $mdDialog, notif_device) {
            $scope.cancel = function() { $mdDialog.cancel(); };
            request.getOneNotifDeviceByDeviceId(notif_device).then(function(resp){
                $scope.device = angular.copy(resp.data);
            });
        }
    };

    self.changeUserStatus = function(ev, o, status) {
        var title = "";
        var content = "";

        if(status == 'ACTIVE'){
            title = 'Allow User';
            content = 'User will able to login and post comment. <br>Are you sure want to <b>Allow User</b> ?';
        } else {
            title = 'Block User';
            content = 'User will unable to login and post comment. <br>Are you sure want to <b>Blocked User</b> ?';
        }

        var confirm = $mdDialog.confirm().title(title);
        confirm.htmlContent(content);
        confirm.targetEvent(ev).ok('YES, SURE!').cancel('CANCEL');

        $mdDialog.show(confirm).then(function() {
            request.updateStatusUserApp(o.id, status).then(function(res){
                if(res.status == 'success'){
                    root.showConfirmDialogSimple('', title +' <b>' +o.name+ '</b> Success !', function(){
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
