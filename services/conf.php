<?php

class CONF {

    /* Flag for demo version */
    public $DEMO_VERSION = false;

    /* Data configuration for database */
    public $DB_SERVER   = "localhost";
    public $DB_USER     = "root";
    public $DB_PASSWORD = "";
    public $DB_NAME     = "bbnews";

    /* FCM key for notification */
    public $FCM_KEY     = "AIzaSyC2FwkmtWYqmr5NbUMLFD2EYf9_aeQ3bsQ";

    public $FCM_TOPIC   = "/topics/ALL-DEVICE";


    /* [ IMPORTANT ] be careful when edit this security code, use AlphaNumeric only*/
    /* This string must be same with security code at Android, if its different android unable to submit data */
    public $SECURITY_CODE = "9vhnSntrajpe6ze0czDI3VrkNmosCRIdH40QDu9fgXbBLSWDRrOdPHGrGPmmhVUpjCaUdP3DJIRtv4s1lc0eyov9ZMYeFNP9Rz3x";

    /* Mailer config ---------------------------------------------------- */

    // change with yours
    public $SMTP_EMAIL      = "sample@your-domain.com";
    public $SMTP_PASSWORD   = "password";
    public $SMTP_HOST       = "mail.your-domain.com";
    public $SMTP_PORT       = 562;

    // for email subject
    public $APP_NAME        = "WTN360 App";
    public $SUBJECT_EMAIL_FORGOT_PASS = "WTN360 App Forgot Password";

}

?>