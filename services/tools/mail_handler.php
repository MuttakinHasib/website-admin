<?php

require_once(realpath(dirname(__FILE__) . "/../conf.php"));
require_once(realpath(dirname(__FILE__) . "/rest.php"));

require_once("mailer/PHPMailer.php");
require_once("mailer/Exception.php");
require_once("mailer/SMTP.php");
use PHPMailer\PHPMailer\PHPMailer;

class MailHandler extends REST {

    private $db         = NULL;
    private $conf       = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->conf = new CONF(); // Create conf class
    }

    public function forgotPassword($user) {
        $email = $user['email'];
        try {
            $mailer = new PHPMailer();
            $mailer->IsSMTP();
            $mailer->SMTPAuth = true;
            // SMTP connection will not close after each email sent, reduces SMTP overhead
            $mailer->SMTPKeepAlive = true;

            $mailer->Host = $this->conf->SMTP_HOST;
            $mailer->Port = $this->conf->SMTP_PORT;
            $mailer->Username = $this->conf->SMTP_EMAIL;
            $mailer->Password = $this->conf->SMTP_PASSWORD;

            $subject = $this->conf->SUBJECT_EMAIL_FORGOT_PASS;
            $mailer->addCustomHeader('X-Entity-Ref-ID', $subject);
            $mailer->Subject = $subject;

            $mailer->SetFrom($this->conf->SMTP_EMAIL, $this->conf->APP_NAME);
            $mailer->addReplyTo($this->conf->SMTP_EMAIL);
            $mailer->addAddress($email, '');
            $template = $this->getEmailTemplate($user);
            $mailer->msgHTML($template);
            $mailer->Send();

        } catch (Exception $e) {
        }
    }

    private function getEmailTemplate($user) {
        // binding data
        $html_template = file_get_contents(realpath(dirname(__FILE__) . "/template/forgot_pass_template.html"));
        foreach ($user as $key => $value) {
            $tagToReplace = "[@$key]";
            $html_template = str_replace($tagToReplace, $value, $html_template);
        }
        return $html_template;
    }

    public function testEmailFunction() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['email'])) $this->responseInvalidParam();
        $email = $this->_request['email'];
        try {
            $mailer = new PHPMailer();
            $mailer->IsSMTP();
            $mailer->SMTPAuth = true;
            // SMTP connection will not close after each email sent, reduces SMTP overhead
            $mailer->SMTPKeepAlive = true;

            $mailer->Host = $this->conf->SMTP_HOST;
            $mailer->Port = $this->conf->SMTP_PORT;
            $mailer->Username = $this->conf->SMTP_EMAIL;
            $mailer->Password = $this->conf->SMTP_PASSWORD;

            $subject = '[TEST] ' . $this->conf->APP_NAME;
            $mailer->addCustomHeader('X-Entity-Ref-ID', $subject);
            $mailer->Subject = $subject;

            $mailer->SetFrom($this->conf->SMTP_EMAIL, $this->conf->APP_NAME);
            $mailer->addReplyTo($this->conf->SMTP_EMAIL);
            $mailer->addAddress($email, '');
            $template = "This is test email content";
            $mailer->msgHTML($template);
            $error = 'Message sent to : '.$email;
            if (!$mailer->Send()) {
                $error = 'Mail error: ' . $mailer->ErrorInfo;
            }
            echo $error;

        } catch (Exception $e) {
        }
    }

}

?>