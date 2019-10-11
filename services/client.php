<?php
require_once(realpath(dirname(__FILE__) . "/tools/rest.php"));
require_once(realpath(dirname(__FILE__) . "/tools/mail_handler.php"));

/*
 * This class handle all communication with Android Client
 */

class CLIENT extends REST {

    private $db = NULL;
    private $app_version = NULL;
    private $news = NULL;
    private $news_comment = NULL;
    private $topic = NULL;
    private $news_gallery = NULL;
    private $user_app = NULL;
    private $notif_device   = NULL;
    private $mail_handler = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->app_version = new AppVersion($this->db);
        $this->news = new News($this->db);
        $this->news_comment = new NewsComment($this->db);
        $this->topic = new Topic($this->db);
        $this->news_gallery = new NewsGallery($this->db);
        $this->user_app = new UserApp($this->db);
        $this->notif_device = new NotifDevice($this->db);
        $this->mail_handler = new MailHandler($this->db);
    }

    /* Cek status version and get some config data */
    public function info() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['version'])) $this->responseInvalidParam();
        $version = (int)$this->_request['version'];
        $query = "SELECT COUNT(DISTINCT a.id) FROM app_version a WHERE version_code = $version AND active = 1";
        $resp_ver = $this->db->get_count($query);
        $info = array("active" => ($resp_ver > 0));
        $response = array("status" => "success", "info" => $info);
        $this->show_response($response);
    }

    /* Response featured Home Data*/
    public function findHomeData() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $featured_news = $this->news->findAllFeatured();
        $featured_topic = $this->topic->findAllFeatured();

        $featured_news_obj = array();
        foreach ($featured_news as $r) {
            unset($r['content'], $r['draft']);
            array_push($featured_news_obj, $r);
        }

        $featured_topic_obj = array();
        foreach ($featured_topic as $r) {
            unset($r['draft']);
            array_push($featured_topic_obj, $r);
        }

        $response = array('status' => 'success', 'featured' => $featured_news_obj, 'topic' => $featured_topic_obj);
        $this->show_response($response);
    }

    /* Response All News POST method*/
    public function findAllNewsPOST() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['count']) || !isset($data['page'])) $this->responseInvalidParam();
        if (!isset($data['q']) || !isset($data['topic_id'])) $this->responseInvalidParam();
        if (!isset($data['col']) || !isset($data['ord'])) $this->responseInvalidParam();
        $feat = isset($data['feat']) ? (int)$data['feat'] : -1;

        $limit = $data['count'];
        $page = $data['page'];
        $q = $data['q'];
        $topic_id = $data['topic_id'];
        $column = $data['col'];
        $order = $data['ord'];

        $response = $this->findAllNews($limit, $page, $q, $topic_id, $column, $order, $feat);
        $this->show_response($response);
    }


    /* Response All News generic*/
    private function findAllNews($limit, $page, $q, $topic_id, $column, $order, $feat) {
        $offset = ($page * $limit) - $limit;
        $count_total = $this->news->allCountPlainForClient($q, $topic_id, $feat);
        $news = $this->news->findAllByPagePlainForClient($limit, $offset, $q, $topic_id, $column, $order, $feat);

        $object_res = array();
        foreach ($news as $r) {
            unset($r['content'], $r['draft']);
            array_push($object_res, $r);
        }
        $count = count($news);
        $response = array('status' => 'success', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'news' => $object_res);
        return $response;
    }

    /* Response All Topic */
    public function findAllTopic() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
        $page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;
        $q = isset($this->_request['q']) && $this->_request['q'] != null ? ($this->_request['q']) : "";

        $offset = ($page * $limit) - $limit;
        $count_total = $this->topic->allCountPlain($q, 1);
        $videos = $this->topic->findAllByPagePlain($limit, $offset, $q, 1);

        $object_res = array();
        foreach ($videos as $r) {
            unset($r['draft']);
            array_push($object_res, $r);
        }
        $count = count($videos);
        $response = array('status' => 'success', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'topics' => $object_res);
        $this->show_response($response);
    }

    /* Response Details News */
    public function findNewsDetails() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];

        $view = isset($this->_request['view']) ? (int)$this->_request['view'] : 0;

        $news = $this->news->findOnePlainForClient($id);
        if (count($news) > 0) {
            $topics = $this->array_column($this->topic->getAllByNewsIdPlainClient($id), 'name');
            $gallery = null;
            if($news['type'] == 'GALLERY') {
                $gallery =  $this->array_column($this->news_gallery->findAllByNewsIdPlain($id), 'name');
            }
            $response = array('status' => 'success', 'news' => $news, 'topics' => $topics, 'gallery' => $gallery);
            if($view == 1) $this->news->incrementTotalViewed($id);
        } else {
            $response = array('status' => 'failed', 'news' => null, 'images' => null);
        }
        $this->show_response($response);
    }

    /* Response All Topic Name */
    public function findAllTopicIdName() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        $topics = $this->topic->findAllForClient();
        $response = array('status' => 'success', 'topics' => $topics);
        $this->show_response($response);
    }

    /* function login for android user */
    public function loginUserApp() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['email']) || !isset($this->_request['password'])) $this->responseInvalidParam();

        $email = $this->_request['email'];
        $password = $this->_request['password'];
        $notif_device = $this->_request['notif_device'];

        $user = $this->user_app->findOneByEmailPassword($email, $password);
        $code = 'SUCCESS';
        if (count($user) <= 0) {
            $user = null;
            $code = 'NOT_FOUND';
        } else if($user['status'] == 'INACTIVE'){
            $user = null;
            $code = 'DISABLED';
        }
        if($code == 'SUCCESS')$this->user_app->updateOneNotifDevice($user['id'], $notif_device);
        $response = array('code' => $code, 'user' => $user);
        $this->show_response($response);
    }

    /* function forgot password for android user */
    public function forgotPasswordUserApp() {
        if ($this->get_request_method() != "GET") $this->response('', 406);

        if (!isset($this->_request['email'])) $this->responseInvalidParam();

        $email = $this->_request['email'];
        $user = $this->user_app->findOneByEmail($email);
        $code = 'SUCCESS';
        if (count($user) <= 0) {
            $this->show_response(array('code' => 'NOT_FOUND'));
        } else if($user['status'] == 'INACTIVE'){
            $this->show_response(array('code' => 'DISABLED'));
        }
        // send email
        $user['password'] = $this->decodePassword($user['password']);
        $this->mail_handler->forgotPassword($user);

        $this->show_response(array('code' => $code));

    }

    /* function register for android user */
    public function registerUserApp() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        if (!isset($this->_request['id']) || !isset($this->_request['email'])) $this->responseInvalidParam();
        if (!isset($this->_request['password']) || !isset($this->_request['name'])) $this->responseInvalidParam();

        $id             = $this->_request['id'];
        $name           = $this->_request['name'];
        $email          = $this->_request['email'];
        $password       = $this->_request['password'];
        $notif_device   = $this->_request['notif_device'];

        $now_time   = round(microtime(true) * 1000);
        $image_name = '';
        $avatar     = null;
        $old_file   = null;
        $base_path  = dirname(dirname(__FILE__)) . '/uploads/user/';

        $code = 'SUCCESS';
        if(isset($_FILES['avatar'])){
            $avatar = $_FILES['avatar'];
            $image_name = 'avatar_'.$now_time.'.png';
        }
        $user_app = array(
            'name' => $name, 'email' => $email, 'password' => $password, 'image' => $image_name, 'status' => 'ACTIVE',
            'notif_device' => $notif_device, 'created_at' => $now_time, 'last_update' => $now_time,
        );

        if ($id < 0){ // for REGISTER user

            $user = $this->user_app->findOneByEmail($email);
            if (count($user) > 0) $this->show_response(array('code' => 'EXIST', 'user' => null));

            $resp = $this->user_app->insertOneObj($user_app);
            if($resp['status'] == 'success') {
                $user = $resp['data'];
            } else {
                $this->show_response(array('code' => 'FAILED', 'user' => null));
            }
        } else { // for update UPDATE user

            $user = $this->user_app->findOneById($id);
            if (count($user) <= 0 || $user['status'] == 'INACTIVE') {
                $this->show_response(array('code' => 'NOT_FOUND', 'user' => null));
            }
            $user_e = $this->user_app->findOneByEmail($email);
            if (count($user_e) > 0 && $user_e['id'] != $id) {
                $this->show_response(array('code' => 'EXIST', 'user' => null));
            }
            if(!isset($_FILES['avatar'])) {
                $user_app['image'] = $user['image'];
            } else {
                $old_file = $base_path.$user['image'];
            }
            $user_app['status'] = $user['status'];
            $user_app['created_at'] = $user['created_at'];
            $user_app = array('id' => $id, 'user_app' => $user_app);

            $resp = $this->user_app->updateOneObj($user_app);
            if($resp['status'] == 'success'){
                $user = $resp['data']['user_app'];
            } else {
                $this->show_response(array('code' => 'FAILED', 'user' => null));
            }
        }

        // uploading avatar if exist
        if(isset($_FILES['avatar']) && $code == 'SUCCESS'){
            if ($old_file != null && file_exists($old_file)) unlink($old_file);

            $target_file = $base_path . $image_name;
            move_uploaded_file($avatar['tmp_name'], $target_file);
        }

        $this->show_response(array('code' => $code, 'user' => $user));
    }

    /* Response All Comment By News ID */
    public function findAllNewsComment() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['news_id'])) $this->responseInvalidParam();
        $limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
        $page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;
        $news_id = (int)$this->_request['news_id'];

        $offset = ($page * $limit) - $limit;
        $count_total = $this->news_comment->countAllByNewsId($news_id);
        $comments = $this->news_comment->findAllByNewsId($limit, $offset, $news_id);

        $count = count($comments);
        $response = array(
            'status' => 'success', 'count' => $count, 'count_total' => $count_total,
            'pages' => $page, 'comments' => $comments
        );
        $this->show_response($response);
    }

    /* Add new comment for news */
    public function addNewsComment() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['news_id']) || !isset($data['comment'])) $this->responseInvalidParam();
        if (!isset($data['user_app_id'])) $this->responseInvalidParam();

        $now_time = round(microtime(true) * 1000);
        $news_id = (int)$data['news_id'];
        $user_app_id = (int)$data['user_app_id'];
        $comment = $data['comment'];
        $news_comment = array(
            'news_id' => $news_id, 'user_app_id' => $user_app_id, 'comment' => $comment,
            'status' => 'SHOW', 'created_at' => $now_time, 'last_update' => $now_time
        );
        $resp = $this->news_comment->findOnePlainDuplicate($news_id, $user_app_id, $comment);
        if (count($resp) > 0) $this->show_response(array('code' => 'EXIST'));

        $resp = $this->news_comment->insertOneObj($news_comment);
        if($resp['status'] == 'success') {
            $this->news->updateTotalComment($news_id);
            $this->show_response(array('code' => 'SUCCESS', 'comment' => $resp['data']));
        } else {
            $this->show_response(array('code' => 'FAILED'));
        }
    }

    public function addUserDevice() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $fcm = json_decode(file_get_contents("php://input"), true);
        if (!isset($fcm['device_id'])) $this->responseInvalidParam();
        $resp = $this->notif_device->insertOne($fcm);
        $this->show_response($resp);
    }

}

?>