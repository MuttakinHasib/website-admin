angular.module('App').controller('AddNewsController', function (
    $rootScope, $scope, $http, $mdToast, $mdDialog, $route,
    $timeout, request, $mdpDatePicker, $mdpTimePicker ) {

    // define variable
    var self            = $scope, root = $rootScope;
    var is_new          = ( root.getCurNewsId() == null );
    var original        = null;
    var now             = new Date().getTime();
    var dir             = "/uploads/news/";

    root.search_enable      = false;
    root.toolbar_menu       = null;
    root.pagetitle          = (is_new) ? 'Add News' : 'Edit News';
    self.button_text        = (is_new) ? 'SAVE' : 'UPDATE';
    self.submit_loading     = false;
    self.image              = {};
    self.gallery            = {};
    self.gallery.valid      = true;
    self.images_obj 		= [];
    self.topic_selected     = [];
    self.news_date          = moment(new Date()).format('DD MMM YYYY HH:mm');
    self.type_array 		= ["ARTICLE", "GALLERY", "VIDEO"];
    root.closeAndDisableSearch();
    var old_img_name = null;

    request.getAllTopic().then(function (resp) {
        self.topic_data = resp.data;
    });
    /* check edit or add new */
    if (is_new) {
        self.topic_valid = false;
        self.image.valid = false;
        original = {
            title: null, content: null, image: null, url: null, total_view : 0, total_comment: 0, date:now,
            type: 'ARTICLE', draft: 1, featured: 0, created_at: now, last_update: now
        };
        self.news = angular.copy(original);
    } else {
        self.image.valid = true;
        self.topic_valid = true;
        self.original_topic = [];
        self.original_tags = [];
        request.getOneNews(root.getCurNewsId()).then(function (resp) {
            original = resp.data;
            self.news = angular.copy(original);
            old_img_name = angular.copy(self.news.image);
        });
        request.getAllTopicByNewsId(root.getCurNewsId()).then(function (resp) {
            for (var i = 0; i < resp.data.length; i++) {
                self.original_topic.push(resp.data[i].id);
            }
            root.sortArrayOfInt(self.original_topic);
            self.topic_selected = angular.copy(self.original_topic);
        });
        request.getAllNewsGalleryByNewsId(root.getCurNewsId()).then(function(resp){
            self.images_obj = angular.copy(resp.data);
        });
    }

    /* for selecting topic*/
    self.toggleTopic = function (i, list) {
        var idx = list.indexOf(i.id);
        if (idx > -1) {
            list.splice(idx, 1);
        } else {
            list.push(i.id);
        }
        root.sortArrayOfInt(list);
        self.topic_valid = (self.topic_selected.length > 0);
    };
    self.isTopicSelected = function (i, list) {
        return list.indexOf(i.id) > -1;
    };

    /* method for submit action */
    /* [0] news_topic done, [1] primary image done, [2] gallery image done */
    self.done_arr = [false, false, false, false];
    self.submit = function (v) {
        self.submit_loading = true;
        self.resp_submit = null;
        self.submit_done = false;
        self.done_arr = [false, false, false, false];

        if (is_new) { // create
            v.image = (self.image.file != null) ? now + root.getExtension(self.image.file) : v.image;
            request.insertOneNews(v).then(function (resp) {
                self.resp_submit = resp;
                if (resp.status == 'success') {
                    self.prepareNewsTopic(resp.data.id);
                    request.insertAllNewsTopic(self.news_topic).then(function () {
                        self.done_arr[0] = true;
                    }); // insert table relation
                    request.uploadFileToUrl(self.image.file, dir, v.image, "").then(function () {
                        self.done_arr[1] = true;
                    }); // upload primary image
                    if(v.type == 'GALLERY' && self.gallery.files && self.gallery.files.length > 0){
                        uploadGalleryImages(resp.data.id, 0);
                    } else { self.done_arr[2] = true; } // upload gallery image
                } else {
                    self.done_arr[0] = true;
                    self.submit_done = true;
                }
            });
        } else {  // update
            v.last_update = now;
            v.image = (self.image.file != null) ? now + root.getExtension(self.image.file) : v.image;
            request.updateOneNews(v.id, v).then(function (resp) {
                self.resp_submit = resp;
                if (resp.status == 'success') {
                    self.prepareNewsTopic(resp.data.id);
                    request.insertAllNewsTopic(self.news_topic).then(function () {
                        self.done_arr[0] = true;
                    }); // insert table relation
                    if (self.image.file != null) {
                        request.uploadFileToUrl(self.image.file, dir, v.image, old_img_name).then(function () {
                            self.done_arr[1] = true;
                        }); // upload primary image
                    } else { self.done_arr[1] = true; }
                    if(v.type == 'GALLERY' && self.gallery.files && self.gallery.files.length > 0){
                        uploadGalleryImages(v.id, 0);
                    } else { self.done_arr[2] = true; } // upload gallery image
                } else {
                    self.done_arr[0] = true;
                    self.submit_done = true;
                }
            });
        }

    };

    /* Submit watch onFinish Checker */
    self.$watchCollection('done_arr', function (new_val, old_val) {
        if (self.submit_done || (new_val[0] && new_val[1] && new_val[2])) {
            $timeout(function () { // give delay for good UI
                if (self.resp_submit.status == 'success') {
                    if (self.send_notif) {
                        self.sendNotification(is_new ? self.resp_submit.data : self.resp_submit.data.news);
                    }
                    root.showConfirmDialogSimple('', self.resp_submit.msg, function () {
                        window.location.href = '#news';
                    });
                } else {
                    root.showInfoDialogSimple('', self.resp_submit.msg);
                }
                self.submit_loading = false;
            }, 1000);
        }
    });

    /* checker when all data ready to submit */
    self.isReadySubmit = function () {
        if (is_new) {
            self.is_clean = angular.equals(original, self.news);
            return (!self.is_clean && self.image.valid && self.topic_valid);
        } else {
            self.is_clean = ( angular.equals(original, self.news)
            && angular.equals(self.original_topic, self.topic_selected)
            && (self.gallery.files != null && self.gallery.files.length == 0) );
            if (self.image.file != null) {
                return (self.topic_valid && self.image.valid);
            } else {
                return (!self.is_clean && self.topic_valid);
            }
        }
    };

    /* for selecting primary image file */
    self.onFileSelect = function (files) {
        self.image.valid = false;
        self.image.file = files[0];
        if (root.constrainFile(files[0])) {
            self.image.valid = true;
        }
        $mdToast.show($mdToast.simple().content("Selected file").position('bottom right'));
    };


    /* for selecting primary image file */
    self.onGalleryFileSelect = function (files) {
        self.gallery.valid = true;
        self.gallery.files = [];
        for (i = 0; i < files.length; i++) {
            if (!root.constrainFile(files[i])) {
                self.gallery.valid = false;
                break;
            }
            self.gallery.files.push(files[i]);
        }
        self.gallery.empty = self.gallery.valid && self.gallery.files.length == 0;
        $mdToast.show($mdToast.simple().content("Selected multi file").position('bottom right'));
    };

    /* uploader for optional images, using recursive method*/
    var uploadGalleryImages = function(n_id, n){
        if(n == 0){
            deleteImages();
            self.images_obj = [];
        }
        if(n < self.gallery.files.length){
            var nfile = self.gallery.files[n];
            var name  	= now + n + "_" + root.getExtension(nfile);
            request.uploadFileToUrl(nfile, dir, name, '').then(function(resp){
                if(resp.status == 'success'){
                    self.images_obj[n] = { news_id:n_id, name:name };
                    uploadGalleryImages(n_id, (n+1),);
                }else{
                    uploadGalleryImages(n_id, n);
                }
            });
        } else {
            if(self.images_obj.length > 0){
                request.insertAllNewsGallery(self.images_obj).then(function(resp){ self.done_arr[2] = true; });
            } else {
                self.done_arr[2] = true;
            }
        }
    };


    var deleteImages = function () {
        if(self.images_obj && self.images_obj.length > 0){
            var image_names = [];
            for(var i = 0; i < self.images_obj.length; i++) {
                image_names.push(self.images_obj[i].name);
            }
            request.deleteFiles(dir, image_names);
        }
    };

    /* normalize news_topic object by adding news id */
    self.prepareNewsTopic = function (id) {
        self.news_topic = [];
        for (var i = 0; i < self.topic_selected.length; i++) {
            var item = {news_id: id, topic_id: self.topic_selected[i]};
            self.news_topic.push(item);
        }
    };

    /* for notification */
    self.sendNotification = function (obj) {
        var title = (is_new) ? root.NEWS_ADD : root.NEWS_UPDATE;
        var body = root.getNotificationBody('NEWS', obj, title, obj.title, null);
        root.requestPostNotification(body, function (resp) {});
    };

    self.cancel = function () {
        window.location.href = '#news';
    };
    self.isNewEntry = function () {
        return is_new;
    };
    self.draftChanged = function (draft) {
        if (draft == 1) self.send_notif = false;
    };
    self.showDatePicker = function(ev) {
        $mdpDatePicker($scope.currentDate, {
            targetEvent: ev
        }).then(function(date) {
            self.showTimePicker(ev, date)
        });
    };
    self.showTimePicker = function(ev, date) {
        $mdpTimePicker($scope.currentTime, {
            targetEvent: ev,
            autoSwitch: true
        }).then(function (time) {
            var date_result = new Date(
                date.getFullYear(), date.getMonth(), date.getDate(),
                time.getHours(), time.getMinutes(), 0
            );
            console.log(date_result);
            self.news_date = moment(date_result).format('DD MMM YYYY HH:mm');
        });
    };

    /* dialog View Image*/
    self.viewImage = function (ev, data) {
        $mdDialog.show({
            controller: ViewImageDialogController,
            parent: angular.element(document.body), targetEvent: ev, clickOutsideToClose: true, file_url: data,
            template: '<md-dialog ng-cloak aria-label="viewImage">' +
            '  <md-dialog-content style="max-width:800px;max-height:810px;" >' +
            '   <img style="margin: auto; max-width: 100%; max-height= 100%;" ng-src="{{file_url}}">' +
            '  </md-dialog-content>' +
            '</md-dialog>'

        })
    };

});

function ViewImageDialogController($scope, $mdDialog, $mdToast, file_url) {
    $scope.file_url = file_url;
}
