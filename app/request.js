angular.module('App').factory("request", function ($http, $cookies) {

	/**
	 * This file is connector between angularJs with REST API(API.php)
	 */

	var api_base = 'services/';

	var obj = {};
	var token = $cookies.get(window.location.origin + '_session_password');
	var config = { headers: { 'Token': token } };

    // NEWS ------------------------------------------------------------------------------------------------------------
    obj.getOneNews = function (id) {
        return $http.get(api_base + 'getOneNews?id='+id);
    };
    obj.getAllNewsByPage = function (page, limit, q, topic_id, sort) {
        return $http.get(
            api_base + 'getAllNewsByPage?page='+page+'&limit='+limit
            +'&col='+sort.column+'&ord='+sort.order+'&q='+q+'&topic_id='+topic_id);
    };
    obj.getAllNewsCount = function(q, topic_id) {
        return $http.get(api_base + 'getAllNewsCount?q='+q+'&topic_id='+topic_id);
    };
    obj.insertOneNews = function (object) {
        return $http.post(api_base + 'insertOneNews', object, config).then(function (results) { return results.data; });
    };
    obj.updateOneNews = function (id, object) {
        var data = {id:id, news:object};
        return $http.post(api_base + 'updateOneNews', data, config).then(function (results) { return results.data; });
    };
    obj.updateOneNews = function (id, object) {
        var data = {id:id, news:object};
        return $http.post(api_base + 'updateOneNews', data, config).then(function (results) { return results.data; });
    };
    obj.deleteOneNews = function (id) {
        return $http.get(api_base + 'deleteOneNews?id='+id, config).then(function (results) { return results.data; });
    };

    // TOPIC -----------------------------------------------------------------------------------------------------------
    obj.getAllTopic  = function () {
        return $http.get(api_base + 'getAllTopic');
    };
    obj.getOneTopic = function (id) {
        return $http.get(api_base + 'getOneTopic?id='+id);
    };
    obj.getAllTopicByPage = function (page, limit, q) {
        return $http.get(api_base + 'getAllTopicByPage?page='+page+'&limit='+limit+'&q='+q);
    };
    obj.getAllTopicCount = function(q) {
        return $http.get(api_base + 'getAllTopicCount?q='+q);
    };
    obj.getAllTopicByNewsId = function(news_id) {
        return $http.get(api_base + 'getAllTopicByNewsId?news_id='+news_id);
    };
    obj.insertOneTopic = function (object) {
        return $http.post(api_base + 'insertOneTopic', object, config).then(function (results) { return results.data; });
    };
    obj.updateOneTopic = function (id, object) {
        var data = {id:id, topic:object};
        return $http.post(api_base + 'updateOneTopic', data, config).then(function (results) { return results.data; });
    };
    obj.deleteOneTopic = function (id) {
        return $http.get(api_base + 'deleteOneTopic?id='+id, config).then(function (results) { return results.data; });
    };

    // NEWS CATEGORY ----------------------------------------------------------------------------------------------
    obj.insertAllNewsTopic= function (object) {
        return $http.post(api_base + 'insertAllNewsTopic', object, config).then(function (results) {
            return results.data;
        });
    };

    // APP VERSION -----------------------------------------------------------------------------------------------------
    obj.getOneAppVersion = function (id) {
        return $http.get(api_base + 'getOneAppVersion?id='+id);
    };
    obj.getAllAppVersionByPage = function (page, limit, q) {
        return $http.get(api_base + 'getAllAppVersionByPage?page='+page+'&limit='+limit+'&q='+q);
    };
    obj.getAllAppVersionCount = function(q) {
        return $http.get(api_base + 'getAllAppVersionCount?q='+q);
    };
    obj.insertOneAppVersion = function (object) {
        return $http.post(api_base + 'insertOneAppVersion', object, config).then(function (results) { return results.data; });
    };
    obj.updateOneAppVersion = function (id, object) {
        var data = {id:id, app_version:object};
        return $http.post(api_base + 'updateOneAppVersion', data, config).then(function (results) { return results.data; });
    };
    obj.deleteOneAppVersion = function (id) {
        return $http.get(api_base + 'deleteOneAppVersion?id='+id, config).then(function (results) { return results.data; });
    };

    // NOTIF HISTORY ---------------------------------------------------------------------------------------------------
    obj.getAllNotifHistoryByPage = function (page, limit, q) {
        return $http.get(api_base + 'getAllNotifHistoryByPage?page=' +page+'&limit='+limit+'&q='+q, config);
    };
    obj.getAllNotifHistoryCount = function(q) {
        return $http.get(api_base + 'getAllNotifHistoryCount?q='+q);
    };

    // NOTIF DEVICE ----------------------------------------------------------------------------------------------------
    obj.getOneNotifDeviceByDeviceId = function (device_id) {
        return $http.get(api_base + 'getOneNotifDeviceByDeviceId?device_id='+device_id, config);
    };

    obj.getAllNotifDeviceByPage = function (page, limit, q) {
        return $http.get(api_base + 'getAllNotifDeviceByPage?page=' +page+'&limit='+limit+'&q='+q, config);
    };
    obj.getAllNotifDeviceCount = function(q) {
        return $http.get(api_base + 'getAllNotifDeviceCount?q='+q);
    };
    obj.sendNotif = function (body) {
        return $http.post(api_base + 'sendNotif', body, config).then(function (results) { return results.data; });
    };

    // USER APP --------------------------------------------------------------------------------------------------------
    obj.getOneUserApp = function (id) {
        return $http.get(api_base + 'getOneUserApp?id=' + id, config);
    };
    obj.updateOneUserApp = function (id, user) {
        var data = { id: id, user_app: user };
        return $http.post(api_base + 'updateOneUserApp', data, config).then(function (results) { return results.data; });
    };
    obj.insertOneUserApp = function (user) {
        return $http.post(api_base + 'insertOneUserApp', user, config).then(function (results) { return results.data; });
    };
    obj.getAllUserAppByPage = function (page, limit, q) {
        return $http.get(api_base + 'getAllUserAppByPage?page='+page+'&limit='+limit+'&q='+q, config);
    };
    obj.getAllUserAppCount = function(q) {
        return $http.get(api_base + 'getAllUserAppCount?q='+q);
    };
    obj.updateStatusUserApp = function (id, status) {
        return $http.get(api_base + 'updateStatusUserApp?id='+id+'&status='+status, config).then(function (results) { return results.data; });
    };

	// USER PANEL ------------------------------------------------------------------------------------------------------
	obj.loginPanel = function (userdata) {
		return $http.post(api_base + 'loginPanel', userdata).then(function (results) { return results; });
	};
	obj.getOneUserPanel = function (id) {
		return $http.get(api_base + 'getOneUserPanel?id=' + id, config);
	};
	obj.updateOneUserPanel = function (id, user) {
		var data = { id: id, user_panel: user };
		return $http.post(api_base + 'updateOneUserPanel', data, config).then(function (results) { return results.data; });
	};
	obj.insertOneUserPanel = function (user) {
		return $http.post(api_base + 'insertOneUserPanel', user, config).then(function (results) { return results.data; });
	};
    obj.getAllUserPanelByPage = function (page, limit, q) {
        return $http.get(api_base + 'getAllUserPanelByPage?page='+page+'&limit='+limit+'&q='+q, config);
    };
    obj.getAllUserPanelCount = function(q) {
        return $http.get(api_base + 'getAllUserPanelCount?q='+q);
    };
    obj.deleteOneUserPanel = function (id) {
        return $http.get(api_base + 'deleteOneUserPanel?id='+id, config).then(function (results) { return results.data; });
    };

    // NEWS GALLERY ----------------------------------------------------------------------------------------------------
    obj.insertAllNewsGallery = function (object) {
        return $http.post(api_base + 'insertAllNewsGallery', object, config).then(function (results) {
            return results.data;
        });
    };
    obj.getAllNewsGalleryByNewsId = function(news_id) {
        return $http.get(api_base + 'getAllNewsGalleryByNewsId?news_id='+news_id);
    };
    obj.deleteNewsGalleryByName = function(name){
        return $http.delete(api_base + 'deleteNewsGalleryByName?name=' + name, config).then(function (results) {
            return results.data;
        });
    };

    // NEWS COMMENT ----------------------------------------------------------------------------------------------------
    obj.getOneNewsComment = function (id) {
        return $http.get(api_base + 'getOneNewsComment?id='+id);
    };
    obj.getAllNewsCommentByPage = function (page, limit, q) {
        return $http.get(api_base + 'getAllNewsCommentByPage?page='+page+'&limit='+limit+'&q='+q);
    };
    obj.getAllNewsCommentCount = function(q) {
        return $http.get(api_base + 'getAllNewsCommentCount?q='+q);
    };
    obj.deleteOneNewsComment = function (id) {
        return $http.get(api_base + 'deleteOneNewsComment?id='+id, config).then(function (results) { return results.data; });
    };
    obj.updateStatusNewsComment = function (id, status) {
        return $http.get(api_base + 'updateStatusNewsComment?id='+id+'&status='+status, config).then(function (results) { return results.data; });
    };

    // DASHBOARD ------------------------------------------------------------------------------
    obj.getDashboardNews = function () {
        return $http.get(api_base + 'getDashboardNews');
    };
    obj.getDashboardOthers = function () {
        return $http.get(api_base + 'getDashboardOthers');
    };

    // CONFIG ----------------------------------------------------------------------------------------------------------
    obj.getAllConfig = function () {
        return $http.get(api_base + 'getAllConfig', config);
    };
    obj.updateAllConfig = function (object) {
        return $http.post(api_base + 'updateAllConfig', object, config).then(function (results) { return results.data; });
    };

    // LICENSE ---------------------------------------------------------------------------------------------------------
    obj.checkLicense = function () {
        return $http.get(api_base + 'checkLicense');
    };
    obj.updateLicense = function(code) {
        return $http.get(api_base + 'updateLicense?code='+code, config);
    };

	// FILE UTILITIES ---------------------------------------------------------------------------------
	obj.uploadFileToUrl = function (f, dir, name, oldname) {
		var fd = new FormData();
		fd.append("file", f);
		fd.append("target_dir", dir);
		fd.append("file_name", name);
		fd.append("old_name", oldname);
		var request = {
			method: 'POST', data: fd,
			url: 'app/uploader/uploader.php',
			headers: { 'Content-Type': undefined }
		};
		return $http(request).then(function (resp) { return resp.data; });
	};

	obj.deleteFiles = function (dir, names) {
		var data = {target_dir:dir, file_names:names};
		var request = {
			method: 'POST', data:data,
			url: 'app/uploader/delete.php',
			headers: { 'Content-Type': 'application/json' }
		};
		return $http(request).then(function (resp) { return resp.data; });
	};

	return obj;
});
