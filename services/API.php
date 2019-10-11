<?php
require_once("tools/rest.php");
require_once("tools/db.php");
require_once("tools/mail_handler.php");
require_once("client.php");
require_once("dashboard.php");
require_once("table/Topic.php");
require_once("table/News.php");
require_once("table/NewsComment.php");
require_once("table/NewsGallery.php");
require_once("table/NotifHistory.php");
require_once("table/AppVersion.php");
require_once("table/NotifDevice.php");
require_once("table/UserApp.php");
require_once("table/UserPanel.php");
require_once("table/Config.php");
require_once("table/License.php");

class API {

    private $db = NULL;

    private $client         = NULL;
    private $topic          = NULL;
    private $license        = NULL;
    private $news           = NULL;
    private $news_comment   = NULL;
    private $news_gallery   = NULL;
    private $app_version    = NULL;
    private $notif_history  = NULL;
    private $notif_device   = NULL;
    private $user_panel     = NULL;
    private $user_app       = NULL;
    private $config         = NULL;
    private $dashboard 	    = NULL;
    private $mail_handler   = NULL;

    public function __construct() {
        $this->db = new DB();
        $this->client           = new CLIENT($this->db);
        $this->news             = new News($this->db);
        $this->license          = new License($this->db);
        $this->news_comment     = new NewsComment($this->db);
        $this->news_gallery     = new NewsGallery($this->db);
        $this->topic            = new Topic($this->db);
        $this->app_version      = new AppVersion($this->db);
        $this->notif_history    = new NotifHistory($this->db);
        $this->notif_device     = new NotifDevice($this->db);
        $this->user_panel       = new UserPanel($this->db);
        $this->user_app         = new UserApp($this->db);
        $this->config           = new Config($this->db);
        $this->mail_handler     = new MailHandler($this->db);
        $this->dashboard        = new DASHBOARD($this->db);
    }

    /*
	 * ALL API Related android client ----------------------------------------------------------------------------------
	 */
    private function info() {
        $this->client->info();
    }

    private function registerDevice() {
        $this->user_panel->checkSecurity();
        $this->client->addUserDevice();
    }

    private function login() {
        $this->user_panel->checkSecurity();
        $this->client->loginUserApp();
    }

    private function register() {
        $this->user_panel->checkSecurity();
        $this->client->registerUserApp();
    }

    private function forgotPassword() {
        $this->user_panel->checkSecurity();
        $this->client->forgotPasswordUserApp();
    }

    private function getHome() {
        $this->client->findHomeData();
    }

    private function listNewsAdv() {
        $this->client->findAllNewsPOST();
    }

    private function getNewsDetails() {
        $this->client->findNewsDetails();
    }

    private function listTopic() {
        $this->client->findAllTopic();
    }

    private function listTopicName() {
        $this->client->findAllTopicIdName();
    }

    private function listNewsComment() {
        $this->user_panel->checkSecurity();
        $this->client->findAllNewsComment();
    }

    private function addComment() {
        $this->user_panel->checkSecurity();
        $this->client->addNewsComment();
    }

    /*
     * ALL API Related DASHBOARD page -----------------------------------------------------------------------------------
     */
    private function getDashboardNews(){
        $this->dashboard->findDashboardNewsData();
    }
    private function getDashboardOthers(){
        $this->dashboard->findDashboardOthersData();
    }

    /*
     * TABLE NEWS TRANSACTION ------------------------------------------------------------------------------------------
     */
    private function getOneNews() {
        $this->news->findOne();
    }

    private function getAllNews() {
        $this->news->findAll();
    }

    private function getAllNewsByPage() {
        $this->news->findAllByPage();
    }

    private function getAllNewsCount() {
        $this->news->allCount();
    }

    private function insertOneNews() {
        $this->user_panel->checkAuth();
        $this->news->insertOne();
    }

    private function updateOneNews() {
        $this->user_panel->checkAuth();
        $this->news->updateOne();
    }

    private function deleteOneNews() {
        $this->user_panel->checkAuth();
        $this->news->deleteOne();
    }

    /*
     * TABLE TOPIC TRANSACTION -----------------------------------------------------------------------------------------
     */
    private function getOneTopic() {
        $this->topic->findOne();
    }

    private function getAllTopic() {
        $this->topic->findAll();
    }

    private function getAllTopicByPage() {
        $this->topic->findAllByPage();
    }

    private function getAllTopicCount() {
        $this->topic->allCount();
    }

    private function getAllTopicByNewsId() {
        $this->topic->getAllByNewsId();
    }

    private function insertOneTopic() {
        $this->user_panel->checkAuth();
        $this->topic->insertOne();
    }

    private function updateOneTopic() {
        $this->user_panel->checkAuth();
        $this->topic->updateOne();
    }

    private function deleteOneTopic() {
        $this->user_panel->checkAuth();
        $this->topic->deleteOne();
    }

    private function insertAllNewsTopic() {
        $this->user_panel->checkAuth();
        $this->topic->deleteInsertAll();
    }

    /*
     * TABLE APP_VERSION TRANSACTION -----------------------------------------------------------------------------------
     */
    private function getOneAppVersion() {
        $this->app_version->findOne();
    }

    private function getAllAppVersionByPage() {
        $this->app_version->findAllByPage();
    }

    private function getAllAppVersionCount() {
        $this->app_version->allCount();
    }

    private function insertOneAppVersion() {
        $this->user_panel->checkAuthAdmin();
        $this->app_version->insertOne();
    }

    private function updateOneAppVersion() {
        $this->user_panel->checkAuthAdmin();
        $this->app_version->updateOne();
    }

    private function deleteOneAppVersion() {
        $this->user_panel->checkAuthAdmin();
        $this->app_version->deleteOne();
    }

    /*
     * TABLE NOTIF HISTORY TRANSACTION ---------------------------------------------------------------------------------
     */
    private function getAllNotifHistoryByPage() {
        $this->user_panel->checkAuthAdmin();
        $this->notif_history->findAllByPage();
    }

    private function getAllNotifHistoryCount() {
        $this->notif_history->allCount();
    }

    /*
     * TABLE NOTIF DEVICE TRANSACTION ----------------------------------------------------------------------------------
     */
    private function getOneNotifDeviceByDeviceId() {
        $this->user_panel->checkAuth();
        $this->notif_device->findOneByDeviceId();
    }
    private function getAllNotifDeviceByPage() {
        $this->user_panel->checkAuthAdmin();
        $this->notif_device->findAllByPage();
    }

    private function getAllNotifDeviceCount() {
        $this->notif_device->allCount();
    }

    private function sendNotif() {
        $this->user_panel->checkAuth();
        $this->notif_device->processNotification();
    }

    /*
     * TABLE USERS APP TRANSACTION -------------------------------------------------------------------------------------
     */
    private function getOneUserApp() {
        $this->user_panel->checkAuthAdmin();
        $this->user_app->findOne();
    }

    private function updateOneUserApp() {
        $this->user_panel->checkAuthAdmin();
        $this->user_app->updateOne();
    }

    private function insertOneUserApp() {
        $this->user_panel->checkAuthAdmin();
        $this->user_app->insertOne();
    }

    private function getAllUserAppByPage() {
        $this->user_panel->checkAuthAdmin();
        $this->user_app->findAllByPage();
    }

    private function getAllUserAppCount() {
        $this->user_app->allCount();
    }

    private function updateStatusUserApp() {
        $this->user_panel->checkAuth();
        $this->user_app->updateStatus();
    }


    /*
	 * TABLE USERS PANEL TRANSACTION -----------------------------------------------------------------------------------
	 */
    private function loginPanel() {
        $this->user_panel->processLogin();
    }

    private function getOneUserPanel() {
        $this->user_panel->checkAuthAdmin();
        $this->user_panel->findOne();
    }

    private function updateOneUserPanel() {
        $this->user_panel->checkAuthAdmin();
        $this->user_panel->updateOne();
    }

    private function insertOneUserPanel() {
        $this->user_panel->checkAuthAdmin();
        $this->user_panel->insertOne();
    }

    private function getAllUserPanelByPage() {
        $this->user_panel->checkAuthAdmin();
        $this->user_panel->findAllByPage();
    }

    private function getAllUserPanelCount() {
        $this->user_panel->allCount();
    }

    private function deleteOneUserPanel() {
        $this->user_panel->checkAuthAdmin();
        $this->user_panel->deleteOne();
    }

    /*
     * TABLE NEWS_GALLERY TRANSACTION ----------------------------------------------------------------------------------
     */
    private function getAllNewsGalleryByNewsId(){
        $this->news_gallery->findAllByNewsId();
    }
    private function getAllNewsGallery(){
        $this->news_gallery->findAll();
    }
    private function insertAllNewsGallery(){
        $this->user_panel->checkAuth();
        $this->news_gallery->insertAll();
    }
    private function deleteNewsGalleryeByName(){
        $this->user_panel->checkAuth();
        $this->news_gallery->delete();
    }

    /*
     * TABLE NEWS COMMENT TRANSACTION ----------------------------------------------------------------------------------
     */
    private function getOneNewsComment() {
        $this->news_comment->findOne();
    }

    private function getAllNewsCommentByPage() {
        $this->news_comment->findAllByPage();
    }

    private function getAllNewsCommentCount() {
        $this->news_comment->allCount();
    }

    private function insertOneNewsComment() {
        $this->user_panel->checkAuth();
        $this->news_comment->insertOne();
    }

    private function updateOneNewsComment() {
        $this->user_panel->checkAuth();
        $this->news_comment->updateOne();
    }

    private function deleteOneNewsComment() {
        $this->user_panel->checkAuth();
        $this->news_comment->deleteOne();
    }

    private function updateStatusNewsComment() {
        $this->user_panel->checkAuth();
        $this->news_comment->updateStatus();
    }

    /*
     * TABLE CONFIG TRANSACTION ----------------------------------------------------------------------------------------
     */
    private function getAllConfig() {
        $this->user_panel->checkAuth();
        $this->config->findAll();
    }

    private function updateAllConfig() {
        $this->user_panel->checkAuth();
        $this->config->updateAll();
    }

    /*
     * TABLE LICENSE TRANSACTION ----------------------------------------------------------------------------------------
     */
    private function checkLicense() {
        $this->license->checkLicense();
    }

    private function updateLicense() {
        //$this->user_panel->checkAuth();
        $this->license->updateLicense();
    }

    /*
     * Email sender trigger
     */
    private function testEmail(){
        $this->user_panel->checkSecurity();
        $this->mail_handler->testEmailFunction();
    }

    /*
     * DATABASE TRANSACTION --------------------------------------------------------------------------------------------
     */
    public function checkResponse() {
        $this->db->checkResponse_Impl();
    }

    /* Dynamically call the method based on the query string
     * Handling direct path to function
     */
    public function processApi() {
        if (isset($_REQUEST['x']) && $_REQUEST['x'] != "") {
            $func = strtolower(trim(str_replace("/", "", $_REQUEST['x'])));
            if ((int)method_exists($this, $func) > 0) {
                $this->$func();
            } else {
                echo 'method not exist';
                exit;
            }
        } else {
            echo 'method not exist';
            exit;
        }
    }

}

// Initiiate Library

$api = new API;
$api->processApi();
?>
