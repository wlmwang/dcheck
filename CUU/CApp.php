<?php
/**
 * 2014-03-06
 * @author wanglm
 *
 */
class CApp
{
	private static $_app = NULL;
	
	/**
	 * 运行方式 
	 * @var object[CCli|CWeb]
	 */	
	private $_tty = NULL;
	
	/**
	 * 路由
	 * @var array
	 */
	private $_router = array();
	
	/**
	 * 控制器
	 * @var object
	 */
	private $_controller = NULL;
	
	/**
	 * 动作
	 * @var sting
	 */
	private $_action = NULL;
	
	/**
	 * conf.php
	 * @var sting
	 */
	private $_conf = array();
	
	/**
	 * 自动加载路径
	 * @var array
	 */
	private $_includePath = array();
	
	public static function app()
	{
		return self::$_app;
	}
	
	public function tty()
	{
		return $this->_tty;
	}
	
	public static function createCApp()
	{
		define('__CUU__',str_replace(array('//','\\'),array('/','/'),dirname(__FILE__)));
		define('__CORE__',__CUU__.'/Core');
		define('__EXT__',__CUU__.'/Ext');
		define('__LIB__',__CUU__.'/Lib');
		define('__VIEW__',__CUU__.'/View');
		//set_include_path(get_include_path().PATH_SEPARATOR.__CORE__);
		
		spl_autoload_register(array('CApp','autoLoad'));
		
		return self::$_app = CFactory::SFactory('CApp');
	}
	
	public function run($_conf=NULL)
	{
		//全局配置
		$this->setConf(self::import(__CUU__.'/Conf/CMain.php'));
		
		//项目配置
		$this->setConf($_conf);
		
		$this->_tty = isset($_SERVER['argv'])? CFactory::SFactory('CCli'): CFactory::SFactory('CWeb');
		
		$this->_router = $this->_tty->getRouter();
		
		$this->_controller = CFactory::createCController($this->_router['controller'],self::$_app);
		
		if (!$this->_controller) 
		{
			CLog::log();
		}
		/**
		 * 运行方法
		 */
		if (method_exists($this->_controller,$this->_router['action']))
		{
			$this->_controller->{$this->_router['action']}();
		}
		else 
		{
			CLog::log();
		}
		/**
		 * 善后处理
		 */
		
	}
	
	public function setConf($_conf)
	{
		if ($_conf && is_array($_conf))
		{
			foreach ($_conf as $key=>$value)
			{
				$this->_conf[$key] = $value;
			}
		}
	}
	
	public function getConf($key = null)
	{
		if ($key)
		{
			return isset($this->_conf[$key])?$this->_conf[$key]:null;
		}
		else
		{
			return $this->_conf;
		}
	}
	
	/**
	 * autoload
	 * @param string $className
	 * @return boolean
	 */
	public static function autoLoad($className)
	{
		$classFile = '';
		if (self::VaildFile(__CORE__.'/'.$className.'.php'))
		{
			$classFile = __CORE__.'/'.$className.'.php';
		}
		elseif (self::VaildFile(__EXT__.'/'.$className.'.php'))
		{
			$classFile = __EXT__.'/'.$className.'.php';
		}
		elseif (self::VaildFile(__LIB__.'/'.$className.'.php'))
		{
			$classFile = __LIB__.'/'.$className.'.php';
		}
		//app
		else
		{
			if (substr($className, -10) =='Controller') 
			{
				if (self::VaildFile(__APP__.'/Controller/'.$className.'.php')) 
				{
					$classFile = __APP__.'/Controller/'.$className.'.php';
				}
			}
			elseif(substr($className, -5) =='Model') 
			{
				if (self::VaildFile(__APP__.'/Model/'.$className.'.php'))
				{
					$classFile = __APP__.'/Model/'.$className.'.php';
				}
			}
		}
		//包含类
		if (!empty($classFile)) 
		{
			self::import($classFile);
			return TRUE;
		}
		return FALSE;
	}
	
	public static function import($file)
	{
		if (self::VaildFile($file))
			require_once $file;
		return FALSE;
	}
	
	public static function VaildFile($file)
	{
		if (file_exists($file) && is_file($file))
		{
			return TRUE;
		}
		return FALSE;
	}
	
	
}
