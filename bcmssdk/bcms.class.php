<?php
/* Copyright(C)
* baidu(beijing) INF-IIS group
* All right reserved
*/
if (! defined ( 'BCMS_API_PATH' )) {
	define ( 'BCMS_API_PATH', dirname ( __FILE__ ) );
}
require_once (BCMS_API_PATH . '/conf/conf.inc.php');
require_once (BCMS_API_PATH . '/libs/requestcore/requestcore.class.php');
require_once (BCMS_API_PATH . '/libs/exception/bcms_exception.php');

/* *****************************************************************************/
/**
 * @Brief Baidu Cloud Message Service PHP API Class
 */
/* *****************************************************************************/
class BaiduBcms {
	/**************************************************************************************
	 *USER MUST PAY ATTENTION
	 *level2 param keys, user may use them 
	 *before call a API, you will prepare params like this:
	 *param_opt[BaiduBcms::XXX], XXX is as fallow:
	 ***************************************************************************************/
	const QUEUE_TYPE = 'queue_type'; //specify queue type, you will use it while creating a queue
	const QUEUE_ALIAS_NAME = 'queue_alias_name'; //specify queue alias name, you will use it while creating a queue
	const DESTINATION = 'destination'; //specify destination, you will use it while subscribing, confirm, cancel etc.
	const LABEL = "label";  //specify grant label
	const USER = "user";  //specify grant user info
	const USERTYPE = "usertype";  //specify grant user type
	const ACTIONS = "actions";  //specify grant actions, must be array
	const EFFECT_START = "effect_start";  //specify grant effect start time, format is YYYY-MM-DD HH-MM-SS
	const EFFECT_END = "effect_end";  //specify grant effect end time, format is YYYY-MM-DD HH-MM-SS
	const MESSAGE = 'message'; //specify message, you will use it while publishing, mailing, smsing etc.
	const MSG_ID = 'msg_id'; //specify msg id, you will use it while fetching.
	const FETCH_NUM = 'fetch_num'; //specify fetch num, you will use it while fetching.
	const ADDRESS = 'address'; //specify email adresses, you will use it while mailing.
	const MAIL_SUBJECT = 'mail_subject'; //specify email subject, you will use it while mailing.
	const TOKEN = 'token'; //specify token, you will use it while confirming, cancel etc.	

	/**************************************************************************************
	 *USER SHOULD PAY ATTENTION
	 *level1 param keys, user may use it in advance usage
	 *before call APIs except create_queue, you must specify a queue to operate on, so prepare params like this:
	 *param_opt[BaiduBcms::XXX]
	 ***************************************************************************************/
	const METHOD = 'method'; //HTTP METHOD, you cannot set it
	const VERSION = 'v'; //if set, API will use it instead of BaiduBcms::VERSION_API
	const CLIENT_ID = 'client_id'; //if set, API will use it instead of BaiduBcms::$ak
	const CLIENT_SECRET = 'client_secret'; //if set, API will use it instead of BaiduBcms::$sk
	const ACCESS_TOKEN = 'access_token';  //specify access_token when use https
	const TIMESTAMP = 'timestamp'; //if not set, set timestamp to be now
	const EXPIRES = 'expires'; //if not set, will use default characteristics
	const SIGN = 'sign'; //specify sign, user must not use it
	const PRODUCT = 'bcms'; //specify product name, user must not use it
	const HOST = 'host';  //specify host name
    const QUEUE_NAME = 'queue_name'; //specify a queue to operate on
	/**************************************************************************************
	 * USER MUST NOT PAY ATTENTION
	 * private data
	 ***************************************************************************************/
	private $_use_ssl = false;
	private $_client_id = NULL;
	private $_client_secret = NULL;
	private $_host = NULL;
	private $_queue_name = NULL;
	private $_return_errorcode = false;
	private $_log_func;
	
	//////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////public functions//////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////
	

	/* *****************************************************************************/
	/**
	 * @Brief set_http_ssl set HTTP OR HTTPS protocol
	 *
	 * @Param $use_ssl true: HTTPS, false: HTTP
	 *
	 * @Returns void
	 */
	/* *****************************************************************************/
	public function set_http_ssl($use_ssl) {
		$this->_use_ssl = ! ! $use_ssl;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief set_return_errorcode set SDK return error code or not
	 *
	 * @Param $use_ssl true: return error code, false: not return error code
	 *
	 * @Returns void
	 */
	/* *****************************************************************************/
	public function set_return_errorcode($is_return) {
		$this->_return_errorcode = ! ! $is_return;
	}

	/* *****************************************************************************/
	/**
	 * @Brief set_log_handler set customer log hander, $log_func cannot be _bcms_default_log
	 * 	void my_log_handler(string $logstr)
	 *
	 * @Param $log_func customer log handler function name
	 *
	 * @Returns true if succ, false else
	 */
	/* *****************************************************************************/
	public function set_log_handler($log_func) {
		if (! $log_func) {
			return false;
		}
		if ('_bcms_default_log' === $log_func) {
			$this->_bcms_log ( 'customer log handler cannot named _bcms_default_log, choose anther name' );
			return false;
		}
		if (! function_exists ( $log_func )) {
			$this->_bcms_log ( '$log_func does not exists in the context' );
			return false;
		}
		$this->_log_func = $log_func;
		return true;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief create_queue create a queue
	 *
	 * @Param $param_opt params, Details see eg for create_queue
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function create_queue($param_opt = NULL) {
		try {
			$param_opt [self::METHOD] = 'create';
			$param_opt [BaiduBcms::QUEUE_NAME] = 'queue';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'create queue succ' : 'create queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief drop_queue drop a queue
	 *
	 * @Param $param_opt  params, Details see eg for drop_queue
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function drop_queue($param_opt = NULL) {
	    try {
			$param_opt [self::METHOD] = 'drop';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'drop queue succ' : 'drop queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	/* *****************************************************************************/
	/**
	 * @Brief subscribe_queue subscribe a queue
	 *
	 * @Param $param_opt params, Details see eg for subscribe_queue
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function subscribe_queue($param_opt = NULL) {
	    try {
			$param_opt [self::METHOD] = 'subscribe';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME, self::DESTINATION) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'subscribe queue succ' : 'subscribe queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief unsubscribe_queue unsubscribe a queue
	 *
	 * @Param $param_opt params, Details see eg for unsubscribe_queue
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function unsubscribe_queue($param_opt = NULL) {
	    try {
			$param_opt [self::METHOD] = 'unsubscribe';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME, self::DESTINATION) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'unsubscribe queue succ' : 'unsubscribe queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief unsubscribeall_queue unsubscribe all queue
	 *
	 * @Param $param_opt params, Details see eg for unsubscribeall_queue
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function unsubscribeall_queue($param_opt = NULL) {
	    try {
			$param_opt [self::METHOD] = 'unsubscribeall';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'unsubscribeall queue succ' : 'unsubscribeall queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief grant_queue grant a queue
	 *
	 * @Param $param_opt params, Details see eg for grant_queue
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function grant_queue($param_opt = NULL) {
	    try {
			$param_opt [self::METHOD] = 'grant';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME, self::LABEL, self::USER, self::ACTIONS) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'grant queue succ' : 'grant queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief revoke_queue revoke a queue
	 *
	 * @Param $param_opt params, Details see eg for revoke_queue
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function revoke_queue($param_opt = NULL) {
	    try {
			$param_opt [self::METHOD] = 'revoke';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME, self::LABEL) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'revoke queue succ' : 'revoke queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}

	/* *****************************************************************************/
	/**
	 * @Brief suspend_queue suspend a queue
	 *
	 * @Param $param_opt params, Details see eg for suspend_queue
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function suspend_queue($param_opt = NULL) {
	    try {
			$param_opt [self::METHOD] = 'suspend';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'suspend queue succ' : 'suspend queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief resume_queue suspend a queue
	 *
	 * @Param $param_opt params, Details see eg for resume_queue
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function resume_queue($param_opt = NULL) {
	    try {
			$param_opt [self::METHOD] = 'resume';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'resume queue succ' : 'resume queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief confirm confirm to subscribe a message
	 *
	 * @Param $param_opt params, Details see eg for confirm
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function confirm_queue($param_opt = NULL) {
		try {
			$param_opt [self::METHOD] = 'confirm';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME, self::TOKEN, self::DESTINATION) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'confirm queue succ' : 'confirm queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief cancel cancel subscribe a message
	 *
	 * @Param $param_opt params, Details see eg for cancel
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function cancel_queue($param_opt = NULL) {
		try {
			$param_opt [self::METHOD] = 'cancel';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME, self::TOKEN, self::DESTINATION) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'cancel queue succ' : 'cancel queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief publish_message publish a message to a queue
	 *
	 * @Param $param_opt params, Details see eg for publish_message
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function publish_message($param_opt = NULL) {
		try {
			$param_opt [self::METHOD] = 'publish';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME, self::MESSAGE) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'publish queue succ' : 'publish queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief publish_messages publish messages to a queue
	 *
	 * @Param $param_opt params, Details see eg for publish_messages
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function publish_messages($param_opt = NULL) {
		try {
			$param_opt [self::METHOD] = 'publishes';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME, self::MESSAGE) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'publishes queue succ' : 'publishes queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief fetch_message fetch a message from a queue
	 *
	 * @Param $param_opt params, Details see eg for fetch_message
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function fetch_message($param_opt = NULL) {
		try {
			$param_opt [self::METHOD] = 'fetch';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'fetch queue succ' : 'fetch queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief mail email a message to less than 10 email address
	 *
	 * @Param $param_opt params, Details see eg for mail
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function mail($param_opt = NULL) {
		try {
			$param_opt [self::METHOD] = 'mail';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME, self::MESSAGE, self::ADDRESS) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'mail queue succ' : 'mail queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	/* *****************************************************************************/
	/**
	 * @Brief sms sms a message to less than 10 sms phone number
	 *
	 * @Param $param_opt params, Details see eg for sms
	 *
	 * @Returns response object if succ, false else
	 */
	/* *****************************************************************************/
	public function sms($param_opt = NULL) {
		try {
			$param_opt [self::METHOD] = 'sms';
			$this->_adjust_opt ( $param_opt );
			$arr_content = array ();
			$this->_bcms_get_sign ( $param_opt, $arr_content, array(self::QUEUE_NAME, self::MESSAGE, self::ADDRESS) );
			$ret = $this->_base_control ( $arr_content );
			$logstr = $ret->isOK () ? 'sms queue succ' : 'sms queue failed: [' . $ret->body . ']';
			$level = $ret->isOK () ? 'TRACE' : 'FATAL';
			$this->_bcms_log ( $logstr, $level );
			if($ret->isOK () || $this->_return_errorcode) {
				return $ret->body;
			}
		} catch ( Exception $ex ) {
			$this->_bcms_log ( $this->_bcms_exception_handler ( $ex ), 'FATAL' );
		}
		return false;
	}
	
	//////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////private functions/////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////
	public function __construct($client_id = NULL, $client_secret = NULL, $host = NULL, $queue_name = NULL) {
		$this->_client_id = $client_id;
		if(is_null($this->_client_id) && defined("BCMS_CLIENT_ID") && !is_null(BCMS_CLIENT_ID) && 0 < strlen(BCMS_CLIENT_ID)) {
			$this->_client_id = BCMS_CLIENT_ID;
		}
		$this->_client_secret = $client_secret;
	    if(is_null($this->_client_secret) && defined("BCMS_CLIENT_SECRET") && !is_null(BCMS_CLIENT_SECRET) && 0 < strlen(BCMS_CLIENT_SECRET)) {
			$this->_client_secret = BCMS_CLIENT_SECRET;
		}
		$this->_host = $host;
	    if(is_null($this->_host) && defined("HOST") && !is_null(HOST) && 0 < strlen(HOST)) {
			$this->_host = HOST;
		}
		$this->_queue_name = $queue_name;
	    if(is_null($this->_queue_name)) {
			$this->_queue_name = 'queue';
		}
		if (method_exists ( $this, '_bcms_default_log' )) {
			$this->_log_func = '_bcms_default_log';
		}
	}
	
	private function _adjust_opt(&$opt) {
		if (! isset ( $opt ) || empty ( $opt ) || ! is_array ( $opt )) {
			throw new BcmsException ( "params must be set" );
		}
		if (! isset ( $opt [self::TIMESTAMP] )) {
			$opt [self::TIMESTAMP] = time ();
		}
		if (isset ( $opt [self::CLIENT_ID] )) {
			$this->_client_id = $opt [self::CLIENT_ID];
		} else {
			$opt [self::CLIENT_ID] = $this->_client_id;
		}
		if (isset ( $opt [self::CLIENT_SECRET] )) {
			$this->_client_secret = $opt [self::CLIENT_SECRET];
		} else {
			$opt [self::CLIENT_SECRET] = $this->_client_secret;
		}
		if (isset ( $opt [self::QUEUE_NAME] )) {
			$this->_queue_name = $opt [self::QUEUE_NAME];
		} else {
			$opt [self::QUEUE_NAME] = $this->_queue_name;
		}
		if (isset ( $opt [self::HOST] )) {
			$this->_host = $opt [self::HOST];
		} else {
			$opt [self::HOST] = $this->_host;
		}
		if ($this->_use_ssl) {
			if (! isset ( $opt [self::ACCESS_TOKEN] )) {
				throw new BcmsException ( "access_token must be set if use https" );
			}
		} else {
			if (! isset ( $opt [self::CLIENT_ID] ) || ! isset ( $opt [self::CLIENT_SECRET] )) {
				throw new BcmsException ( "client_id and client_secret must be set if use http" );
			}
		}
	}
	
	public function _bcms_get_sign(&$opt, &$arr_content, $arr_need = array()) {
		$arr_data = array ();
		$arr_content = array();
		$arr_need [] = self::TIMESTAMP;
		$arr_need [] = self::METHOD;
		if ($this->_use_ssl) {
			$arr_need [] = self::ACCESS_TOKEN;
		} else {
			$arr_need [] = self::CLIENT_ID;
			if (isset ( $opt [self::EXPIRES] )) {
				$arr_need [] = self::EXPIRES;
			}
		}
		if (isset ( $opt [self::VERSION] )) {
			$arr_need [] = self::VERSION;
		}
                $arr_exclude = array(self::QUEUE_NAME, self::HOST, self::CLIENT_SECRET);
		foreach ( $arr_need as $key ) {
			if (! isset ( $opt [$key] )) {
				throw new BcmsException ( "$key must be set" );
			}
			if(in_array($key, $arr_exclude)) {
				continue;
			}
			$arr_data [$key] = $opt [$key];
			$arr_content [$key] = $opt [$key];
		}
		foreach ( $opt as $key => $value ) {
			if (! in_array ( $key, $arr_need ) && !in_array($key, $arr_exclude)) {
				$arr_data [$key] = $value;
				$arr_content [$key] = $value;
			}
		}
		ksort ( $arr_data );
		if ($this->_use_ssl) {
			$url = "https://" . $this->_host . "/rest/2.0/" . self::PRODUCT . "/";
		} else {
			$url = "http://" . $this->_host . "/rest/2.0/" . self::PRODUCT . "/";
		}
		if (! is_null ( $this->_queue_name )) {
			$url .= $this->_queue_name;
		} else {
			$url .= "queue";
		}
		$basic_string = "POST" . $url;
		foreach ( $arr_data as $key => $value ) {
			$basic_string .= $key . "=" . $value;
		}
		$basic_string .= $opt [self::CLIENT_SECRET];
		$sign = md5 ( urlencode ( $basic_string ) );
		$arr_content [self::SIGN] = $sign;
	}
	
	private function _bcms_exception_handler(Exception $ex) {
		$err_info = $ex->getMessage ();
		$line = $ex->getLine ();
		$file = $ex->getFile ();
		$err_str = 'an excetpion caught, message: ' . $err_info . ', file: ' . $file . ', line: ' . $line;
		return $err_str;
	}
	
	private function _bcms_default_log($logstr, $level) {
		//echo "[BCMS LOG] ${level}: $logstr\n";
	}
	
	private function _bcms_log($logstr, $level = 'TRACE') {
		/*if ($this->_log_func !== '_bcms_default_log') {
			call_user_func ( $this->_log_func, $logstr, $level );
		} else {
			call_user_func ( array ($this, $this->_log_func ), $logstr, $level );
		}*/
	}
	
	private function _base_control($opt) {
		$content = '';
		foreach ( $opt as $k => $v ) {
			if(is_string($v))
			{
				$v = urlencode($v);
			}
			$content .= $k . '=' . $v . '&';
		}
		$content = substr ( $content, 0, strlen ( $content ) - 1 );
		if ($this->_use_ssl) {
			$url = "https://" . $this->_host . "/rest/2.0/" . self::PRODUCT . "/";
		} else {
			$url = "http://" . $this->_host . "/rest/2.0/" . self::PRODUCT . "/";
		}
		if (! is_null ( $this->_queue_name )) {
			$url .= $this->_queue_name;
		} else {
			$url .= "queue";
		}
		$this->_bcms_log ( "url: $url", "TRACE" );
		$this->_bcms_log ( "method: POST", "TRACE" );
		$this->_bcms_log ( "body: $content", "TRACE" );
		$request = new RequestCore ( $url );
		$headers ['Content-Type'] = 'application/x-www-form-urlencoded';
		$headers ['User-Agent'] = 'Baidu Message Service Phpsdk Client';
		foreach ( $headers as $header_key => $header_value ) {
			$header_value = str_replace ( array ("\r", "\n" ), '', $header_value );
			if ($header_value !== '') {
				$request->add_header ( $header_key, $header_value );
			}
		}
		$request->set_method ( 'POST' );
		$request->set_body ( $content );
		$request->send_request ();
		return new ResponseCore ( $request->get_response_header (), $request->get_response_body (), $request->get_response_code () );
	}
}
;
?>
