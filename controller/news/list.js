angular.module('App').controller('NewsController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $route, request) {
    var self = $scope;
    var root = $rootScope;

    root.toolbar_menu = { title: 'Add News' };
    root.pagetitle = 'News';

    self.loading = true;
    self.topic_id = root.getStateTopicId();
    self.max_item = root.getStateMaxItem();
    self.max_item_array = [];
    self.image_path = 'uploads/news/';
    self.sort_by = root.sort_by;
    self.sort_by_selected = root.getStateSortBy();

    $scope.$watch("topic_id", function(val, old) { if(val != old){
        root.getStateTopicId(val); self.paging.current = 1; loadPages();
    } });
    $scope.$watch("max_item", function(val, old) { if(val != old){
        root.setStateMaxItem(val); self.paging.current = 1; loadPages();
    } });
    $scope.$watch("sort_by_selected", function(val, old) { if(val != old){
        root.setStateSortBy(val); self.paging.current = 1; loadPages();
    } });

    for(var i = 1; i<5; i++){
        var _value = 20*i;
        var _text = _value+" items";
        self.max_item_array.push({value:_value, text:_text});
    }

    root.search_enable = true;

    // receiver barAction from rootScope
    self.$on('barAction', function (event, data) {
        root.setCurNewsId("");
        window.location.href = '#create_news';
    });

    // receiver submitSearch from rootScope
    self.$on('submitSearch', function (event, data) {
        self.q = data;
        loadPages();
    });

    request.getAllTopic().then(function(resp){
        var temp_topic = {id:-1, name:'All Topic'};
        self.topic_data = resp.data;
        self.topic_data.unshift(temp_topic);
    });

    // load pages from database and display
    function loadPages() {
        $_q = self.q ? self.q : '';
        self.paging.limit = self.max_item;
        root.setStatePage(self.paging.current);
        request.getAllNewsCount($_q, self.topic_id).then(function (resp) {
            self.paging.total = Math.ceil(resp.data / self.paging.limit);
            self.paging.modulo_item = resp.data % self.paging.limit;
        });
        $limit = self.paging.limit;
        $current = (self.paging.current * self.paging.limit) - self.paging.limit + 1;
        if (self.paging.current == self.paging.total && self.paging.modulo_item > 0) {
            self.limit = self.paging.modulo_item;
        }
        var sort = self.sort_by[self.sort_by_selected];
        request.getAllNewsByPage($current, $limit, $_q, self.topic_id, sort).then(function (resp) {
            self.news = resp.data;
            self.loading = false;
        });
    }

    // pagination property
    self.paging = {
        total: 0, // total whole item
        current: root.getStatePage(), // start page
        step: 3, // count number display
        limit: self.max_item, // max item per page
        modulo_item: 0,
        onPageChanged: loadPages
    };

    self.editNews = function(ev, v) {
        root.setCurNewsId(v.id);
        window.location.href = '#create_news';
    };

    self.detailsNews = function(ev, w) {
        $mdDialog.show({
            controller          : DetailsNewsControllerDialog,
            templateUrl         : 'view/news/details.html',
            parent              : angular.element(document.body),
            targetEvent         : ev,
            clickOutsideToClose : true,
            news           : w
        })
    };

    self.deleteNews= function(ev, v) {
        var confirm = $mdDialog.confirm().title('Delete Confirmation');
        confirm.content('Are you sure want to delete News with ID '+v.id+' ?');
        confirm.targetEvent(ev).ok('OK').cancel('CANCEL');

        var images = [v.image];
        request.getAllNewsGalleryByNewsId(v.id).then(function(resp){
            var images_obj = angular.copy(resp.data);
            for (i = 0; i < images_obj.length; i++) {
                images.push(images_obj[i].name);
            }
        });

        var dir = "/uploads/news/";
        $mdDialog.show(confirm).then(function() {
            request.deleteOneNews(v.id).then(function(res){
                if(res.status == 'success'){
                    request.deleteFiles(dir, images).then(function(res){ });
                    root.showConfirmDialogSimple('', 'Delete News with ID '+v.id+' <b>Success!</b>', function(){
                        window.location.reload();
                    });
                }else{
                    root.showInfoDialogSimple('', 'Opps , Failed delete News with ID '+v.id);
                }
            });
        });

    };

    /* dialog Publish confirmation*/
    self.publishDialog = function (ev, o) {
        $mdDialog.show({
            controller : PublishNewsDialogCtl,
            parent: angular.element(document.body), targetEvent: ev, clickOutsideToClose: true, obj: o,
            template:
            '<md-dialog ng-cloak aria-label="publishData">' +
            '  <md-dialog-content class="md-dialog-content">' +
            '   <h2 class="md-title">Publish Confirmation</h2> ' +
            '   <p>Are you sure want to publish News : <b>{{obj.name}}</b> ?</p><br>' +
            '   <md-checkbox ng-model="send_notif">Send Notification to users</md-checkbox>' +
            '   <div layout="row"> <span flex></span>' +
            '       <md-button ng-if="!submit_loading" class="md-warn" ng-click="cancel()" >CANCEL</md-button>' +
            '       <md-button ng-click="publish()" class="md-primary">YES</md-button>' +
            '   </div>' +
            '  </md-dialog-content>' +
            '</md-dialog>'
        });
        function PublishNewsDialogCtl($scope, $mdDialog, $mdToast, obj) {
            $scope.obj = angular.copy(obj);
            $scope.cancel = function() { $mdDialog.cancel(); };
            $scope.publish = function() {
                $scope.obj.draft = 0;
                request.updateOneNews($scope.obj.id, $scope.obj).then(function(resp){
                    if(resp.status == 'success'){
                        if($scope.send_notif) $scope.sendNotification(obj);
                        root.showConfirmDialogSimple('', 'Publish News '+obj.name+' <b>Success!</b>', function(){
                            window.location.reload();
                        });
                    }else{
                        var failed_txt = 'Opps , <b>Failed Publish</b> News '+obj.name;
                        if(resp.msg != null) failed_txt = resp.msg;
                        root.showInfoDialogSimple('', failed_txt);
                    }
                });
            };

            /* for notification when publish*/
            $scope.sendNotification = function(obj){
                var title = root.NEWS_ADD;
                var body = root.getNotificationBody('NEWS', obj, title, obj.title, null);
                root.requestPostNotification(body, function (resp) {});
            }
        }
    };

});

function DetailsNewsControllerDialog($rootScope, $scope, $mdDialog, request, news) {
    var self        = $scope;
    self.news	    = news;
    self.topic   = [];
    self.tags       = [];
    self.image_url	= "";
    self.hide   = function() { $mdDialog.hide(); };
    self.cancel = function() { $mdDialog.cancel(); };

    self.image_url = "uploads/news/" + news.image;
    request.getAllTopicByNewsId(news.id).then(function(resp){
        self.topic =  resp.data;
    });

    request.getAllNewsGalleryByNewsId(news.id).then(function(resp){
        self.gallery = resp.data;
    });
}

