<?php
/**
 * Mini2SAE - PHP framework for SAE
 *
 * @author Caleng Tan <tcm1024@gmail.com>
 * @copyright Copyright(C)2010, Caleng Tan
 * @link http://code.google.com/p/fabos/
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version Mini2SAE v0.2
 */
define('ROOT_DIR', __DIR__.'/../');

/**
 * Mini2SAE核心类
 * @final
 * @package include
 */
final class App
{
	public static $disnames  = array("","reg","login","logout","useroption","fileupload","test");
	/**
	 * 单例对象
	 * @static
	 * @access private
	 * @var array
	 */
	private static $mObject = array();
	/**
	 * App配置
	 * @static
	 * @access public
	 * @var array
	 */
	public  static $mConfig = array();
	/**
	 * App路由列表
	 * @static
	 * @access private
	 * @var array
	 */
	private static $mRoute  = array();
	/**
	 * 模板参数
	 * @static
	 * @access private
	 * @var array
	 */
	private static $mTmpValue = array();
	/**
	 * 当前URL地址URI
	 * @static
	 * @access private
	 * @var string
	 */
	private static $mCurrUri  = '';
	/**
	 * 当前调用方法
	 * @static
	 * @access private
	 * @var string
	 */
	private static $mCurrMethod = '';
	/**
	 * 当前请求的资源类型
	 * @static
	 * @access private
	 * @var string
	 */
	private static $mCurrContentType = '';
	/**
	 * 浏览器缓存时间
	 * @static
	 * @access private
	 * @var int
	 */
	private static $mCurrCacheTime = 0;
	/**
	 * 显示公共模板
	 * @static
	 * @access private
	 * @var boolean
	 */
	private static $mCurrLayout = true;
	/**
	 * SAE图像实例对象
	 * @static
	 * @access public
	 * @var object
	 */
	public static $mImage = null;
	/**
	 * SAE数据库实例对象
	 * @static
	 * @access public
	 * @var object
	 */
	public static $mDb    = null;
	/**
	 * SAE memcache连接标识符
	 * @static
	 * @access public
	 * @var object
	 */
	public static $mMemcache = null;
	/**
	 * App开始运行时间
	 * @static
	 * @access private
	 * @var int
	 */
	private static $mStartTime = 0;

	/**
	 * App初始化
	 * @static
	 * @access public
	 */
	
	public static $test=1;
	public static function init()
	{
		self::startTime();
		session_start();
		//获取项目配置
		self::$mConfig = self::config('project');
		
		if (self::$mConfig['DB_DSN'])   self::connectDb();

		//分析当前URI
		self::analysisUri();
	}

	/**
	 * 分析当前请求URL地址，获取URI
	 * @static
	 * @access private
	 */
	private static function analysisUri()
	{
		$pos = strrpos($_SERVER['REQUEST_URI'], '?');
		if ($pos !== false) {
			$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, $pos);
		}
		list(self::$mCurrUri, self::$mCurrContentType) = explode('.', $_SERVER['REQUEST_URI']);
		self::$mCurrMethod = $_SERVER['REQUEST_METHOD'];
		self::$mCurrContentType = empty(self::$mCurrContentType) ? 'html' : self::$mCurrContentType;
	}

	/**
	 * 设置APP路由列表
	 * Usage:
	 * <code>
	 *  App::loader('GET /mini', 'test');
	 *  App::loader('GET /mini/book', array('class'=>'name'), 'xml', 100);
	 * </code>
	 * @static
	 * @access public
	 * @param string $pUri
	 * @param string $pAction
	 * @param string $pContentType
	 * @param int $pCacheTime
	 */
	public static function loader($pUri, $pAction, $pContentType = 'html', $pCacheTime = 0)
	{

		self::$mRoute[] = array('uri' => $pUri, 'action' => $pAction,
			'content_type' => $pContentType, 'cache' => $pCacheTime);
	}

	/**
	 * 根据URI获取路由列表中对应的方法名
	 * @static
	 * @access private
	 * @param string $pUri
	 * @return mixed
	 */
	private static function getAction($pUri = '')
	{
		$action = null;
		$pUri = empty($pUri) ? self::$mCurrUri : $pUri;
		$pUri = self::$mCurrMethod.' '.
			($pUri{strlen($pUri) -1} == '/' && strlen($pUri) > 1 ? substr($pUri, 0, -1) : $pUri);
		
		foreach (self::$mRoute as $item) {
			if ($pUri == $item['uri'] && self::$mCurrContentType == $item['content_type']) {
				$action = $item['action'];
				self::$mCurrCacheTime = $item['cache'];
				break;
			}
		}

		if (empty($action)) {
			self::redirectError(self::$mConfig['ERR_URL']);
		}

		return $action;
	}

	/**
	 * App运行
	 * @static
	 * @access public
	 */
	public static function run()
	{
		self::call();
	}

	/**
	 * 将App ini配置文件转化成数组
	 * @static
	 * @access public
	 * @param string $pFileName
	 * @return array
	 */
	public static function config($pFileName)
	{
		$pFileName = ROOT_DIR.'config/'.$pFileName.'.ini';
		if (file_exists($pFileName)) {
			return parse_ini_file($pFileName);
		}
	}

	/**
	 * 导入整个目录包括子目录的所有php文件
	 * @static
	 * @access public
	 * @param string $pPath
	 */
	public static function import($pPath)
	{
		$pPath = ROOT_DIR.$pPath;
		if (is_dir($pPath)) {
			$dir = dir($pPath);
			while (false !== ($entry = $dir->read())) {
				if ($entry != '.' && $entry != '..') {
					$entry = $pPath.'/'.$entry;
					if (is_dir($entry)) {
						self::import($entry);
					} else {
						if (substr($entry, strrpos($entry, '.') + 1) == 'php') {
							include_once $entry;
						}
					}
				}
			}
		} else {
			self::redirectError(self::$mConfig['ERR_PATH']);
		}
	}
	
	public static function importPage($pPath)
	{
		$pPath = ROOT_DIR.$pPath;
		include_once $pPath;
	}

	/**
	 * 显示模板
	 * @static
	 * @access public
	 * @param string $pTempName
	 */
	public static function display($pTempName)
	{
		$file_path = ROOT_DIR.'template/'.$pTempName.'.php';
		if (file_exists($file_path)) {
			self::setVar('TEMPLATE_FILE', $file_path);
			extract(self::$mTmpValue);
			
			if (self::$mCurrLayout === true) {
				include_once ROOT_DIR.'template/layout.php';
			} else {
				include_once $file_path;
			}

			self::$mTmpValue = array();
			unset($file_path);
		} else {
			self::redirectError(self::$mConfig['ERR_TEMPLATE']);
		}
	}
	
	public static function displayPage($pTempName)
	{
		$file_path = ROOT_DIR.'template/'.$pTempName;
		if (file_exists($file_path)) {
			self::setVar('TEMPLATE_FILE', $file_path);
			extract(self::$mTmpValue);
			
			if (self::$mCurrLayout === true) {
				include_once ROOT_DIR.'template/layout.php';
			} else {
				include_once $file_path;
			}

			self::$mTmpValue = array();
			unset($file_path);
		} else {
			self::redirectError(self::$mConfig['ERR_TEMPLATE']);
		}
	}


	/**
	 * 设置模板参数
	 * @static
	 * @access public
	 * @param string $pVar
	 * @param mixed $pValue
	 */
	public static function setVar($pVar, $pValue)
	{
		self::$mTmpValue[$pVar] = $pValue;
	}

	/**
	 * 设置显示layout
	 * @static
	 * @access public
	 * @param boolean  $pIsset
	 */
	public static function setLayout($pIsset = true)
	{
		self::$mCurrLayout = (bool) $pIsset;
	}

	/**
	 * 设置页面标题
	 * @static
	 * @access public
	 * @param string $pValue
	 */
	public static function setTitle($pValue)
	{
		self::setVar('TEMPLATE_TITLE', $pValue);
	}

	/**
	 * 设置页面MATE
	 * Usage:
	 * <code>
	 *  App::setMeta(array('keywords'=>'content', 'description'=>'content'));
	 * </code>
	 * @static
	 * @access public
	 * @param array $pMeta
	 */
	public static function setMeta($pMeta = array())
	{
		if (array_key_exists('keywords', $pMeta)) {
			$pMeta['keywords'] = self::$mConfig['TEMPLATE_KEYWORDS'];
		}
		if (array_key_exists('description', $pMeta)) {
			$pMeta['description'] = self::$mConfig['TEMPLATE_DESCRIPTION'];
		}

		self::setVar('TEMPLATE_META', $pMeta);
	}

	/**
	 * 设置载入CSS和JS文件名
	 * @static
	 * @access public
	 * @param string $pCssFileName 以逗号分隔多个CSS文件名
	 * @param string $pJsFileName 以逗号分隔多个JS文件名
	 */
	public static function setCssAndJs($pCssFileName = '', $pJsFileName = '')
	{
		if (!empty($pCssFileName)) {
			self::setVar('TEMPLATE_CSS', $pCssFileName);
		}

		if (!empty($pJsFileName)) {
			self::setVar('TEMPLATE_JS', $pJsFileName);
		}
	}

	/**
	 * 输出META、CSS、JS的HTML内容
	 * @static
	 * @access public
	 * @param array $pArr
	 * @param string $pType META|JS|CSS
	 */
	public static function setOutput($pArr, $pType)
	{
		if (!empty($pArr)) {
			foreach ($pArr as $k => &$v) {
				if ($pType == 'META') {
					$html = '<meta name="'.$k.'" content="'.$v.'" />'."\n";
					echo $html;
				} else if ($pType == 'CSS' && !empty($v)) {
					$html  = '<link rel="stylesheet" type="text/css" href="';
					$html .= self::urlTemplate("css/$v.css").'" />'."\n";
					echo $html;
				} else if ($pType == 'JS' && !empty($v)) {
					$html  = '<script type="text/javascript" src="';
					$html .= self::urlTemplate("js/$v.js").'"></script>'."\n";
					echo $html;
				}
			}
		} else if (empty($pArr) && $pType == 'META') {
			$html  = '<meta name="keywords" content="';
			$html .= self::$mConfig['TEMPLATE_KEYWORDS'].'" />'."\n";
			$html .= '<meta name="description" content="';
			$html .= self::$mConfig['TEMPLATE_DESCRIPTION'].'" />'."\n";
			echo $html;
		}
	}

	/**
	 * 获取template下js、css、image资源url地址
	 * Usage:
	 * <code>
	 *  App::urlTemplate('js/filename.js');
	 *  App::urlTemplate('css/filename.css');
	 *  App::urlTemplate('image/filename.jpg');
	 * </code>
	 * @static
	 * @access public
	 * @param string $pPath
	 * @return string
	 */
	public static function urlTemplate($pPath)
	{
		return 'template/'.$pPath;
	}

	/**
	 * 页面跳转
	 * @static
	 * @access public
	 * @param string $pUrl
	 * @param string $pMode LOCATION|REFRESH|META|JS
	 */
	public static function redirect($pUrl = '', $pMode = 'LOCATION')
	{
		$url = $pUrl;
		switch ($pMode) {
			case 'LOCATION':
				if (headers_sent()) {
					self::redirectError(self::$mConfig['ERR_HEADERSENT']);
				} else {
					header("Location: {$url}");
					exit;
				}
			case 'REFRESH':
				if (headers_sent()) {
					self::redirectError(self::$mConfig['ERR_HEADERSENT']);
				} else {
					header("Refresh: 0; url='".$url."'");
					exit;
				}
			case 'META':
				echo "<mate http-equiv='refresh' content='0; url='".$url."' />";
				exit;
			case 'JS':
				echo "<script type='text/javascript'>";
				echo "window.location.href='".$url."';";
				echo "</script>";
				exit;
		}
	}

	/**
	 * 载入指定URI内容
	 * @static
	 * @access public
	 * @param string $pUri
	 */
	public static function call($pUri = '')
	{
		self::import('lib');
		$action = self::getAction($pUri);
		
		try {
			call_user_func(self::getAction($pUri));
		} catch (Exception $e) {
			self::redirectError(self::$mConfig['ERR_METHOD']);
		}
	}

	/**
	 * 单例对象
	 * @static
	 * @access public
	 * @param string $pClassName
	 * @return object
	 */
	public static function getInstance($pClassName)
	{
		if (!array_key_exists($pClassName, self::$mObject)
			&& class_exists($pClassName)) {
				self::$mObject[$pClassName] = new $pClassName;
			}
			return self::$mObject[$pClassName];
		}

		/**
		 * 页面开始执行时间
		 * @static
		 * @access public
		 */
		public static function startTime()
		{
			self::$mStartTime = microtime(true);
		}

		/**
		 * 计算页面总执行时间
		 * @static
		 * @access public
		 * @return float
		 */
		public static function runTime()
		{
			return sprintf('%.6f', microtime(true) - self::$mStartTime);
		}

		/**
		 * 连接SAE数据库
		 * @static
		 * @access private
		 */
		public static function connectDb()
		{
			if (self::$mConfig['SAE_ENV']) {
				self::$mDb = self::getInstance('SaeMysql');
				self::$mDb->setCharset(self::$mConfig['DB_CHARSET']);
			} else {
				try {
					self::$mDb = new PDO(self::$mConfig['DB_MASTER_DSN'], 
						self::$mConfig['DB_MASTER_USER'], self::$mConfig['DB_MASTER_PASSWORD']);
				} catch (PDOException $e) {
					self::redirectError($e->getMessage());
				}
			}
		}

		/**
		 * 关闭SAE数据库连接
		 * @static
		 * @access public
		 */
		public static function closeDb()
		{
			self::$mDb->closeDb();
		}

		/**
		 * 数据表的增C、删D、改U
		 * Usage:
		 * <code>
		 *  $id = App::exec('C', 'table', array(k=>v));
		 *  App::exec('U', 'table', array(k=>v), 'condition');
		 *  App::exec('D', 'table', '', 'condition');
		 * </code>
		 * @static
		 * @access public
		 * @param array $pArr 支持一维数组
		 * @param string $pCond
		 * @return int|boolean
		 */
		public static function exec($pMethod, $pTab, $pArr = array(), $pCond = '')
		{
			if (!empty($pArr) && is_array($pArr)) {
				$str = '';
				foreach ($pArr as $k => $v) $str .= $k.'="'.$v.'",';
				$str = substr($str, 0, -1);
			}

			switch ($pMethod) {
				case 'C':
					$sql = 'INSERT INTO '.$pTab.' SET '.$str;
					break;
				case 'U':
					$sql  = 'UPDATE '.$pTab.' SET '.$str;
					$sql .= empty($pCond) ? '' : (' WHERE '.$pCond);
					break;
				case 'D':
					$sql = 'DELETE FROM '.$pTab.' WHERE '.$pCond;
					break;
				case 'R':
					$sql='SELECT * FROM '.$pTab;
					$sql .= empty($pCond) ? '' : (' WHERE '.$pCond);
					return self::$mDb->getData($sql);
					break;
			}

			self::$mDb->runSql($sql);
			if (self::$mDb->errno() != 0) {
				self::redirectError(self::$mDb->errmsg());
			} else {
				return $pMethod == 'C' ? self::$mDb->lastId() : true;
			}
		}

		public static function execRead($pTab, $pCond = '', $pSel='*'){
			$sql='SELECT '.$pSel.' FROM '.$pTab;
			$sql .= empty($pCond) ? '' : (' WHERE '.$pCond);
			return self::$mDb->getData($sql);
		}
		
		public static function execString($pMethod,$sql){
			switch ($pMethod) {
				case 'R':
					return self::$mDb->getData($sql);
					break;
			}
			
			self::$mDb->runSql($sql);
			if (self::$mDb->errno() != 0) {
				self::redirectError(self::$mDb->errmsg());
			} else {
				return $pMethod == 'C' ? self::$mDb->lastId() : true;
			}
		}
		
		public static function send_mail( $email , $subject , $content ){
			$m = new SaeMail(array("from"=>"Possibility","to"=>$email,"smtp_host"=>"smtp.gmail.com","smtp_port"=>"587","smtp_username"=>"cchaojie@gmail.com","smtp_password"=>"cj19861229","subject"=>$subject,"content"=>$content,"content_type"=>"HTML"));
			$m->quickSend( $email , $subject , $content , "cchaojie@gmail.com" , "cj19861229" , "smtp.gmail.com" , "587");
			if($m->send()){return "true";}
			return $m->errmsg();
		}
		
		public static function v( $str )
		{
			return isset( $_REQUEST[$str] ) ? $_REQUEST[$str] : false;
		}

		public static function z( $str )
		{
			return strip_tags( $str );
		}
		
		public static function t( $str )
		{
			return trim($str);
		}

		// session management
		public static function ss( $key )
		{
			return isset( $_SESSION[$key] ) ?  $_SESSION[$key] : false;
		}

		public static function ss_set( $key , $value )
		{
			return $_SESSION[$key] = $value;
		}
		
		public static function ck($key)
		{
			return isset( $_COOKIE[$key] ) ?  $_COOKIE[$key] : false;
		}

		public static function ck_set($key,$value)
		{
			setcookie($key, $value, time()+3600*24*30);
			return $_COOKIE[$key];
		}

		public static function ck_remove($key)
		{
			setcookie($key, "", time()-3600);
		}	
		
		public static function logout()
		{
			$_SESSION = array();
			header("location:/");
		}
		
		public static function isLogin()
		{
			if(self::ss("user"))
				return true;
			else if(self::ck("username")&&self::ck("password"))
			{
				self::connectDb();
				$data=self::exec('R','users',array(),"username='".ck("username")."' and password='".ck("password")."'");
				self::closeDb();
				
				if($data){
					self::ss_set("user",$data[0]);
					return true;
				}
			}
			return false;
		}
		
		public static function getLoginUserID()
		{
			$data=self::ss("user");
			if($data)
				return $data["userid"];
			else return 0;
		}
		
		public static function getLoginUserName()
		{
			$data=self::ss("user");
			if($data)
				return $data["username"];
			else return $_SERVER["SERVER_ADDR"];
		}
		
		public static function getGuidValue(){
			$computer_name = $_SERVER["SERVER_NAME"];  
			$ip = $_SERVER["SERVER_ADDR"];  
			$guid = new Guid($computer_name, $ip); 
			return $guid->toString();
		}
		
		public function DateDiff($part, $begin, $end)
		{
			$diff = strtotime($end) - strtotime($begin);
			switch($part)
			{
				case "y": $retval = bcdiv($diff, (60 * 60 * 24 * 365)); break;
				case "m": $retval = bcdiv($diff, (60 * 60 * 24 * 30)); break;
				case "w": $retval = bcdiv($diff, (60 * 60 * 24 * 7)); break;
				case "d": $retval = bcdiv($diff, (60 * 60 * 24)); break;
				case "h": $retval = bcdiv($diff, (60 * 60)); break;
				case "n": $retval = bcdiv($diff, 60); break;
				case "s": $retval = $diff; break;
			}
			return $retval;
		}

		public function DateAdd($part, $number, $date)
		{
			$date_array = getdate(strtotime($date));
			$hor = $date_array["hours"];
			$min = $date_array["minutes"];
			$sec = $date_array["seconds"];
			$mon = $date_array["mon"];
			$day = $date_array["mday"];
			$yar = $date_array["year"];
			switch($part)
			{
				case "y": $yar += $number; break;
				case "q": $mon += ($number * 3); break;
				case "m": $mon += $number; break;
				case "w": $day += ($number * 7); break;
				case "d": $day += $number; break;
				case "h": $hor += $number; break;
				case "n": $min += $number; break;
				case "s": $sec += $number; break;
			}
			return date("Y-m-d H:i:s", mktime($hor, $min, $sec, $mon, $day, $yar));
		}
		/**
		 * 错误处理页面
		 * @static
		 * @access public
		 * @param string $pErrStr
		 */
		public static function redirectError($pErrStr = '')
		{
			if (self::$mConfig['DEBUG']) {
				$html  = "<html>\n<head>\n<title>$pErrStr</title>\n";
				$html .= "<style type='text/css'>\n";
				$html .= "body{font-family:Arial;font-size:14px} ";
				$html .= "h2{color:#F00;border-bottom:1px solid #F00;line-height:30px} ";
				$html .= "span{color:#666;border-top:1px dashed #666;display:block;margin-top:20px}";
				$html .= "</style>\n</head>\n<body>";
				$html .= "<h2>ERROR: $pErrStr</h2>\n";
				$html .= self::backtrace();
				$html .= '<span>Time: '.self::runTime()."MS, Memory: ".(memory_get_usage()/1024)."KB, ";
				$html .= 'Power by '.self::$mConfig['VERSION']."</span>\n";
				$html .= "</body>\n</html>";
				exit($html);
			} else {
				exit;
			}
		}

		/**
		 * 追溯PHP程序执行顺序
		 * @static
		 * @access private
		 * @return string
		 */
		private static function backtrace()
		{
			$output = "Backtrace:\n";
			$backtrace = debug_backtrace();

			foreach ($backtrace as $bt) {
				$args = '';
				foreach ($bt['args'] as $a) {
					if (!empty($args)) {
						$args .= ', ';
					}
					switch (gettype($a)) {
						case 'integer':
						case 'double':
							$args .= $a;
							break;
						case 'string':
							$a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
							$args .= "\"$a\"";
							break;
						case 'array':
							$args .= 'Array('.count($a).')';
							break;
						case 'object':
							$args .= 'Object('.get_class($a).')';
							break;
						case 'resource':
							$args .= 'Resource('.strstr($a, '#').')';
							break;
						case 'boolean':
							$args .= $a ? 'True' : 'False';
							break;
						case 'NULL':
							$args .= 'Null';
							break;
						default:
							$args .= 'Unknown';
					}
				}
				$output .= "<br />\n#{$bt['line']} Call: ";
				$output .= "{$bt['class']}{$bt['type']}{$bt['function']}($args) - {$bt['file']}<br />\n";
			}
			return $output;
		}
}