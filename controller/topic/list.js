angular.module('App').controller('TopicController', function ($rootScope, $scope, $http, $mdToast, $mdDialog, $cookies, request) {
	var self = $scope;
	var root = $rootScope;

	root.search_enable = true;
	root.toolbar_menu = { title: 'Add Topic' };
	root.pagetitle = 'Topic';
	
	// receiver barAction from rootScope
	self.$on('barAction', function (event, data) {
		root.setCurTopicId("");
		window.location.href = '#create_topic';
	});
	
	// receiver submitSearch from rootScope
	self.$on('submitSearch', function (event, data) {
		self.q = data;
		self.loadPages();
	});
	
	self.loadPages = function () {
		$_q = self.q ? self.q : '';
        root.setStatePageCat(self.paging.current);
		request.getAllTopicCount($_q).then(function (resp) {
			self.paging.total = Math.ceil(resp.data / self.paging.limit);
			self.paging.modulo_item = resp.data % self.paging.limit;
		});
		$limit = self.paging.limit;
		$current = (self.paging.current * self.paging.limit) - self.paging.limit + 1;
		if (self.paging.current == self.paging.total && self.paging.modulo_item > 0) {
			self.limit = self.paging.modulo_item;
		}
		request.getAllTopicByPage($current, $limit, $_q).then(function (resp) {
			self.topic = resp.data;
			self.loading = false;
			//console.log(JSON.stringify(resp.data));
		});
	};

	//pagination property
	self.paging = {
		total: 0, // total whole item
		current: root.getStatePageCat(), // start page
		step: 3, // count number display
		limit: 20, // max item per page
		modulo_item: 0,
		onPageChanged: self.loadPages
	};
	
	self.editTopic = function(ev, p) {
		root.setCurTopicId(p.id);
		window.location.href = '#create_topic';
	};

	self.deleteTopic = function(ev, p) {
		var confirm = $mdDialog.confirm().title('Delete Confirmation');
			confirm.content('Are you sure want to delete Topic : '+p.name+' ?');
			confirm.targetEvent(ev).ok('OK').cancel('CANCEL');

        var dir = "/uploads/topic/";
		$mdDialog.show(confirm).then(function() {
			request.deleteOneTopic(p.id).then(function(res){
				if(res.status == 'success'){
                    request.deleteFiles(dir, [p.icon]).then(function(res){ });
				    root.showConfirmDialogSimple('', 'Delete Topic '+p.name+' Success !', function(){
				        window.location.reload();
				    });
				}else{
				    root.showInfoDialogSimple('', 'Opps , Failed Delete Topic '+p.name);
				}
			});
		});

	};

	/* dialog View Icon*/
    self.viewIcon = function (ev, f) {
        $mdDialog.show({
            controller : ViewImageDialogController,
            parent: angular.element(document.body), targetEvent: ev, clickOutsideToClose: true, file_url: f,
            template: '<md-dialog ng-cloak aria-label="viewImage">' +
            '  <md-dialog-content style="max-width:800px;max-height:810px;" >' +
            '   <img style="margin: auto; max-width: 100%; max-height= 100%;" ng-src="{{file_url}}">' +
            '  </md-dialog-content>' +
            '</md-dialog>'

        })
    };

    /* dialog Publish confirmation*/
    self.publishDialog = function (ev, o) {
        $mdDialog.show({
            controller : PublishTopicDialogCtl,
            parent: angular.element(document.body), targetEvent: ev, clickOutsideToClose: true, obj: o,
            template:
            '<md-dialog ng-cloak aria-label="publishData">' +
            '  <md-dialog-content class="md-dialog-content">' +
            '   <h2 class="md-title">Publish Confirmation</h2> ' +
            '   <p>Are you sure want to publish Topic : {{obj.name}} ?</p>' +
            '   <div layout="row"> <span flex></span>' +
            '       <md-button ng-if="!submit_loading" class="md-warn" ng-click="cancel()" >CANCEL</md-button>' +
            '       <md-button ng-click="publish()" class="md-primary">YES</md-button>' +
            '   </div>' +
            '  </md-dialog-content>' +
            '</md-dialog>'
        });
        function PublishTopicDialogCtl($scope, $mdDialog, $mdToast, obj) {
            $scope.obj = angular.copy(obj);
            $scope.cancel = function() { $mdDialog.cancel(); };
            $scope.publish = function() {
                $scope.obj.draft = 0;
                request.updateOneTopic($scope.obj.id, $scope.obj).then(function(resp){
                    self.resp_submit = resp;
                    if(resp.status == 'success'){
                        root.showConfirmDialogSimple('', 'Publish Topic '+obj.name+' Success !', function(){
                            window.location.reload();
                        });
                    }else{
                        var failed_txt = 'Opps , Failed Publish Topic '+obj.name;
                        if(resp.msg != null) failed_txt = resp.msg;
                        root.showInfoDialogSimple('', failed_txt);
                    }
                });
            };
        }
    };

});
