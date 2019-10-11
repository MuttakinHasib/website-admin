angular.module('App', [
	'ngMaterial', 'ngRoute', 'ngMessages', 'ngCookies', 'ngSanitize', 'cl.paging', 'textAngular', 'colorpicker.module',
	'mdPickers'
]);

/* Theme Configuration
 */
angular.module('App').config(function ($mdThemingProvider) {

	// Use as Primary color
	var myPrimary = $mdThemingProvider.extendPalette('blue-grey', {
			'500': '263238',
			'contrastDefaultColor': 'dark'
		});

	// Use as Accent color
	var myAccent = $mdThemingProvider.extendPalette('grey', {
			'500': '333333',
        'contrastDefaultColor': 'dark'
		});

	// Register the new color palette
	$mdThemingProvider.definePalette('myPrimary', myPrimary);

	// Register the new color palette
	$mdThemingProvider.definePalette('myAccent', myAccent);

	$mdThemingProvider.theme('default')
	.primaryPalette('myPrimary')
	.accentPalette('myAccent');
});

/* Menu Route Configuration */

angular.module('App').config(['$routeProvider',
	function ($routeProvider) {
		$routeProvider.

		when('/dashboard', { templateUrl: 'view/dashboard/dashboard.html', controller: 'DashboardController' }).
		when('/about', { templateUrl: 'view/about/about.html', controller: 'AboutController' }).
		when('/application', { templateUrl: 'view/application/list.html', controller: 'ApplicationController' }).

        when('/news', { templateUrl: 'view/news/list.html', controller: 'NewsController' }).
        when('/create_news', { templateUrl: 'view/news/create.html', controller: 'AddNewsController' }).

		when('/topic', { templateUrl: 'view/topic/list.html', controller: 'TopicController' }).
        when('/create_topic', { templateUrl: 'view/topic/create.html', controller: 'AddTopicController' }).

        when('/comment', { templateUrl: 'view/comment/list.html', controller: 'CommentController' }).

        when('/notif_history', { templateUrl: 'view/notification/history.html', controller: 'NotifHistoryController' }).
        when('/notif_device', { templateUrl: 'view/notification/device.html', controller: 'NotifDeviceController' }).

		when('/user', { templateUrl: 'view/user/list.html', controller: 'UserController' }).

		when('/setting_admin', { templateUrl: 'view/setting/admin_list.html', controller: 'AdminController' }).
		when('/setting_config', { templateUrl: 'view/setting/config.html', controller: 'ConfigController' }).

		when('/login', { templateUrl: 'view/login.html', controller: 'LoginController' }).

		otherwise({ redirectTo: '/dashboard' });
	}
]);



angular.module('App').factory('focus', function($timeout, $window) {
    return function(id) {
		// timeout makes sure that is invoked after any other event has been triggered.
		// e.g. click events that need to run before the focus or inputs elements that are in a disabled state but are enabled when those events are triggered.
		$timeout(function() {
			var element = $window.document.getElementById(id);
			if(element)element.focus();
		});
	};
});

angular.module('App').run(function ($location, $rootScope, $cookies) {
	$rootScope.$on('$routeChangeSuccess', function (event, current, previous) {
		// $rootScope.title = current.$$route.title;
	});

    $rootScope.$on('$locationChangeStart', function (event, next, current) {
        $rootScope.isLogin	= true;
        if(!$rootScope.isCookieExist()){
            $location.path('/login');
        } else {
            $rootScope.isLogin	= false;
        }
    });
});

angular.module('App').filter('cut', function () {
	return function (value, wordwise, max, tail) {
		if (!value) return '';

		max = parseInt(max, 10);
		if (!max) return value;
		if (value.length <= max) return value;

		value = value.substr(0, max);
		if (wordwise) {
			var lastspace = value.lastIndexOf(' ');
			if (lastspace != -1) {
				//Also remove . and , so its gives a cleaner result.
				if (value.charAt(lastspace-1) == '.' || value.charAt(lastspace-1) == ',') {
					lastspace = lastspace - 1;
				}
				value = value.substr(0, lastspace);
			}
		}

		return value + (tail || ' …');
	};
});
