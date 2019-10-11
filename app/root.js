angular.module('App').controller('RootCtrl', function ($rootScope, $scope, $mdSidenav, $mdToast, $mdDialog, $cookies, focus, request) {

    var self = $scope;
    var root = $rootScope;

    /* panel name and version */
    root.PANEL_NAME = "WTN360";
    root.PANEL_VERSION = "1.0";

    /* Constanta String data */
    root.NEWS_UPDATE = "News Updated";
    root.NEWS_ADD = "News Added";

    /* expired session for admin login*/
    var SESSION_EXPIRED = 1; // in days

    /* maximum file size */
    var MAX_FILE_SIZE = 1; // in MB

    /* Data for side menu
     * icon reference : https://material.io/icons/
     */
    self.sidenav = {
        actions: [
            {id : 100, name: 'DASHBOARD', icon: 'data_usage', link: '#dashboard', sub: false},
            {id : 200, name: 'NEWS', icon: 'subject', link: '#news', sub: false},
            {id : 300, name: 'TOPIC', icon: 'apps', link: '#topic', sub: false},
            {id : 900, name: 'COMMENT', icon: 'comment', link: '#comment', sub: false},
            {id : 400, name: 'APP', icon: 'adb', link: '#application', sub: false},
            {
                id : 500, name: 'NOTIFICATION', icon: 'notifications', sub: true,
                sub_menu : [
                    { name : "History", link: '#notif_history'},
                    { name : "Device", link: '#notif_device'}
                ]
            },
            {id : 600, name: 'USER', icon: 'person_outline', link: '#user', sub: false},
            {
                id : 700, name: 'SETTING', icon: 'settings', sub: true,
                sub_menu : [
                    { name : "Admin", link: '#setting_admin'},
                    { name : "Config", link: '#setting_config'}
                ]

            },
            {id : 800, name: 'ABOUT', icon: 'error', link: '#about', sub: false}
        ]
    };

    self.bgColor = '#EEEEEE';
    self.bgColorLight = '#FFFFFF';
    self.black = '#000000';

    self.iconColor = '#BDBDBD';
    self.menuTextColor = '#616161';

    // flag toolbar action button
    root.search_enable = false;
    root.search_show = false;

    root.base_url = window.location.origin;
    self.uid_key = root.base_url + '_session_uid';
    self.uid_name = root.base_url + '_session_name';
    self.uid_email = root.base_url + '_session_email';
    self.uid_password = root.base_url + '_session_password';
    self.uid_role = root.base_url + '_session_role';

    root.sort_by = [
        {id:0, label : "Time : New to Old", column:"created_at", order:"DESC"},
        {id:1, label : "Time : Old to New", column:"created_at", order:"ASC"},
        {id:4, label : "View : High to Low", column:"total_view", order:"DESC"},
        {id:5, label : "View : Low to High", column:"total_view", order:"ASC"}
    ];

    // retrieve session data
    self.user = {
        name: $cookies.get(self.uid_name),
        email: $cookies.get(self.uid_email),
        role: $cookies.get(self.uid_role)
    };

    // when bar action clicked
    root.barAction = function (ev) {
        root.$broadcast('barAction', "");
    };

    // when search icon click
    root.searchAction = function (ev) {
        focus('search_input');
        root.search_show = true;
        root.$broadcast('searchAction', null);
    };

    // when search close
    root.closeSearch = function (ev) {
        root.search_show = false;
        root.$broadcast('submitSearch', "");
    };

    // when search text submit
    root.submitSearch = function (ev, q) {
        root.$broadcast('submitSearch', q);
    };
    // when search text submit by press enter
    root.keypressAction = function (k_ev, q) {
        if (k_ev.which === 13) {
            root.$broadcast('submitSearch', q);
        }
    };

    root.closeAndDisableSearch = function () {
        root.search_enable = false;
        root.search_show = false;
    };

    // toggle drawer menu
    self.toggleSidenav = function () {
        $mdSidenav('left').toggle();
    };

    self.doLogout = function (ev) {
        var confirm = $mdDialog.confirm().title('Logout Confirmation')
            .content('Are you sure want to logout from user : ' + root.getSessionName() + ' ?')
            .targetEvent(ev)
            .ok('OK').cancel('CANCEL');
        $mdDialog.show(confirm).then(function () {
            // clear session
            root.clearCookies();
            window.location.href = '#login';
            $mdToast.show($mdToast.simple().content('Logout Success').position('bottom right'));
        });
    };

    root.clearCookies = function () {
        // saving session
        $cookies.remove(self.uid_key, null);
        $cookies.remove(self.uid_name, null);
        $cookies.remove(self.uid_email, null);
        $cookies.remove(self.uid_role, null);
        $cookies.remove(self.uid_password, null);
    };

    root.saveCookies = function (id, name, email, password, role) {
        // saving session
        var now = new Date();
        now.setDate(now.getDate() + SESSION_EXPIRED);
        $cookies.put(self.uid_key, id, {expires: now});
        $cookies.put(self.uid_name, name);
        $cookies.put(self.uid_email, email);
        $cookies.put(self.uid_role, role);
        if (password != '*****') $cookies.put(self.uid_password, password);
    };

    root.isCookieExist = function () {
        var uid = $cookies.get(self.uid_key);
        var name = $cookies.get(self.uid_name);
        var email = $cookies.get(self.uid_email);
        var password = $cookies.get(self.uid_password);
        if (uid == null || name == null || email == null || password == null) {
            return false;
        }
        return true;
    };

    root.getSessionUid = function () {
        return $cookies.get(self.uid_key);
    };
    root.getSessionName = function () {
        return $cookies.get(self.uid_name);
    };
    root.getSessionEmail = function () {
        return $cookies.get(self.uid_email);
    };
    root.getSessionRole = function () {
        return $cookies.get(self.uid_role);
    };

    self.directHref = function (href) {
        root.sub_obj = '';
        self.toggleSidenav();
        window.location.href = href;
    };

    root.sub_obj = '';
    root.subMenuAction = function (ev, obj) {
        root.sub_obj = obj;
        window.location.href = obj.link;
    };

    root.sortArrayOfInt = function (array_of_int) {
        array_of_int.sort(function (a, b) {
            return a - b
        });
    };

    // for editing news
    root.setCurNewsId = function (news_id) {
        $cookies.put(root.base_url + 'cur_news_id', news_id);
    };
    root.getCurNewsId = function () {
        var news_id = $cookies.get(root.base_url + 'cur_news_id');
        return (news_id != "") ? news_id : null;
    };

    // for editing topic
    root.setCurTopicId = function (topic_id) {
        $cookies.put(root.base_url + 'cur_topic_id', topic_id);
    };
    root.getCurTopicId = function () {
        var topic_id = $cookies.get(root.base_url + 'cur_topic_id');
        return (topic_id != "") ? topic_id : null;
    };

    root.getExtension = function (f) {
        return (f.type == "image/jpeg" ? '.jpg' : '.png');
    };
    root.constrainFile = function (f) {
        return ((f.type == "image/jpeg" || f.type == "image/png") && f.size <= ( MAX_FILE_SIZE * 1000000 ));
    };

    root.constrainFilePng = function (f) {
        return (f.type == "image/png" && f.size <= ( MAX_FILE_SIZE * 1000000 ));
    };

    root.findValue = function (config, code) {
        for (var i = 0; i < config.length; ++i) {
            var obj = config[i];
            if (obj.code == code) return obj.value;
        }
    };

    root.diffArray = function (master, target) {
        var result = [];
        for (var i = 0; i < master.length; i++) {
            if (target.indexOf(master[i]) === -1) result.push(master[i]);
        }
        return result;
    };

    // show dialog confirmation
    root.showConfirmDialogSimple = function (title, msg, callback) {
        var confirm = $mdDialog.confirm().title(title).htmlContent(msg).ok('OK');
        $mdDialog.show(confirm).then(callback);
    };
    root.showConfirmDialog = function (title, msg, callback) {
        var confirm = $mdDialog.confirm().title(title).htmlContent(msg);
        confirm.ok('OK').cancel('CANCEL');
        $mdDialog.show(confirm).then(callback);
    };

    // show dialog info
    root.showInfoDialogSimple = function (title, msg) {
        var alert = $mdDialog.alert().title(title).htmlContent(msg).ok('CLOSE');
        $mdDialog.show(alert)
    };

    // Send notification method
    root.requestPostNotification = function (body, callback) {
        request.sendNotif(body).then(function (resp) {
            callback(resp);
        });
    };

    root.getNotificationBody = function (type, obj, title, content, reg_id) {
        var body = {title:title, content:content, type:type, link:null, image:null, reg_id:reg_id};
        if (obj != null) {
            body.obj_id = obj.id;
            body.image = obj.image;
        }
        return body;
    };

    /* Filtering menu by user panel role */
    if(root.getSessionRole() != 'ADMIN'){
        self.sidenav.actions = hideSidenavMenu(angular.copy(self.sidenav.actions), 100); // app
        self.sidenav.actions = hideSidenavMenu(angular.copy(self.sidenav.actions), 400); // app
        self.sidenav.actions = hideSidenavMenu(angular.copy(self.sidenav.actions), 500); // notif
        self.sidenav.actions = hideSidenavMenu(angular.copy(self.sidenav.actions), 600); // user
        self.sidenav.actions = hideSidenavMenu(angular.copy(self.sidenav.actions), 700); // setting
    }

    function hideSidenavMenu(arr, id) {
        return arr.filter(function(el){
            return el.id != id;
        });
    }

    // for save sate listing
    root.setStateMaxItem = function (max_item) {
        $cookies.put(root.base_url + 'state_max_item', max_item);
    };
    root.setStateTopicId = function (cat_id) {
        $cookies.put(root.base_url + 'state_topic_id', cat_id);
    };
    root.setStateSortBy = function (sort_by) {
        $cookies.put(root.base_url + 'state_sort_by', sort_by);
    };
    root.setStatePage = function (page) {
        $cookies.put(root.base_url + 'state_page', page);
    };
    root.setStatePageCat = function (page) {
        $cookies.put(root.base_url + 'state_page_cat', page);
    };

    root.getStateMaxItem = function () {
        var val = $cookies.get(root.base_url + 'state_max_item');
        return ( val == null ) ? 20 : val;
    };
    root.getStateTopicId = function () {
        var val = $cookies.get(root.base_url + 'state_topic_id');
        return ( val == null ) ? -1 : val;
    };
    root.getStateSortBy = function () {
        var val = $cookies.get(root.base_url + 'state_sort_by');
        return ( val == null ) ? 0 : val;
    };
    root.getStatePage = function () {
        var val = $cookies.get(root.base_url + 'state_page');
        return ( val == null ) ? 1 : val;
    };
    root.getStatePageCat = function () {
        var val = $cookies.get(root.base_url + 'state_page_cat');
        return ( val == null ) ? 1 : val;
    };



    // license check
    if(root.isCookieExist()){
        request.checkLicense().then(function (resp) {
            if(resp.data != 'ACTIVE'){
               return true;
            }
        });
    }

});
