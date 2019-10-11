<?php
require_once(realpath(dirname(__FILE__) . "/tools/rest.php"));

/*
 * This class handle all data display at dashboard
 */
class DASHBOARD extends REST{

    private $db             = NULL;
    private $news           = NULL;
    private $topic          = NULL;
    private $news_comment   = NULL;
    private $config         = NULL;
    private $app_version    = NULL;
    private $notif_device   = NULL;
    private $user_app       = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db               = $db;
        $this->news             = new News($this->db);
        $this->topic            = new Topic($this->db);
        $this->news_comment     = new NewsComment($this->db);
        $this->config           = new Config($this->db);
        $this->app_version      = new AppVersion($this->db);
        $this->notif_device     = new NotifDevice($this->db);
        $this->user_app         = new UserApp($this->db);
    }

    public function findDashboardNewsData(){
        $news = array(
            'published' => 0, 'draft' => 0,
            'total_comment' => 0, 'total_view' => 0,
            'ARTICLE' => 0, 'GALLERY' => 0, 'VIDEO' => 0 );

        $topic = array(
            'published' => 0, 'draft' => 0
        );

        $news['published'] = $this->news->countByDraftPlain(0);
        $news['draft'] = $this->news->countByDraftPlain(1);
        $news['total_comment'] = $this->news_comment->allCountPlain();
        $news['total_view'] = $this->news->countTotalViewPlain(1);

        $news['ARTICLE'] = $this->news->countByTypePlain('ARTICLE');
        $news['GALLERY'] = $this->news->countByTypePlain('GALLERY');
        $news['VIDEO'] = $this->news->countByTypePlain('VIDEO');

        $topic['published'] = $this->topic->countByDraftPlain(0);
        $topic['draft'] = $this->topic->countByDraftPlain(1);

        $data = array('news' => $news, 'topic' => $topic);
        $this->show_response($data);
    }

    public function findDashboardOthersData(){
        $app = array('active' => 0, 'inactive' => 0);
        $setting = array('featured_topic' => 0, 'featured_news' => 0);
        $user = array('notif_device' => 0, 'app' => 0);

        $setting_result = $this->config->findAllPlain();

        $app['inactive'] = $this->app_version->countInactiveVersion();
        $app['active'] = $this->app_version->countActiveVersion();

        $setting['featured_topic'] = $this->getValue($setting_result, 'FEATURED_TOPIC');
        $setting['featured_news'] = $this->getValue($setting_result, 'FEATURED_NEWS');

        $user['notif_device'] = $this->notif_device->allCountPlain();
        $user['app'] = $this->user_app->allCountPlain("");

        $data = array('app' => $app, 'setting' => $setting, 'user' => $user);
        $this->show_response($data);

    }

    private function getValue($data, $code){
        foreach($data as $d){
            if($d['code'] == $code){
                return $d['value'];
            }
        }
    }
}
?>