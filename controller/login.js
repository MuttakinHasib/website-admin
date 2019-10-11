angular.module('App').controller('LoginController', function ($rootScope, $scope, $http, $mdToast, $route, $timeout, request) {
	var self = $scope;
	var root = $rootScope;

	if($rootScope.isCookieExist()){ window.location.href = '#dashboard'; }

	root.isLogin = true;
	root.toolbar_menu = null;

	$rootScope.pagetitle = 'Login';
	self.submit_loading = false;
	self.panel_name = root.PANEL_NAME;
    self.userdata = { username : "user_news", password: "user" };

    self.doLogin = function () {
        self.submit_loading = true;
        request.loginPanel(self.userdata).then(function (result) {
            var resp = result.data;
            $timeout(function () { // give delay for good UI
                self.submit_loading = false;
                if (resp == "") {
                    $mdToast.show($mdToast.simple().content('Login Failed').position('bottom right'));
                    return;
                }
                if(resp.status == "success"){
                    // saving session
                    root.saveCookies(
                        resp.user_panel.id, resp.user_panel.name,
                        resp.user_panel.email, resp.user_panel.password, resp.user_panel.role
                    );
                    $mdToast.show($mdToast.simple().content('Login Success').position('bottom right'));
                    window.location.href = '#dashboard';
                    window.location.reload();
                } else {
                    $mdToast.show($mdToast.simple().content('Login Failed').position('bottom right'));
                }
            }, 1000);
            //console.log(JSON.stringify(result.data));
        });
    };

});
