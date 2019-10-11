angular.module('App').controller('DashboardController', function ($rootScope, $scope, request) {
    var self = $scope;
    var root = $rootScope;

    root.closeAndDisableSearch();
    root.toolbar_menu = null;
    $rootScope.pagetitle = 'Dashboard';

    request.getDashboardNews().then(function (resp) {
        self.news = resp.data.news;
        self.topic = resp.data.topic;
    });

    request.getDashboardOthers().then(function (resp) {
        self.app = resp.data.app;
        self.setting = resp.data.setting;
        self.user = resp.data.user;
    });

});
