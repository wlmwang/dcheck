<?php
/**
 * 2014-03-06
 * @author wanglm
 *
 */
class CWeb
{
	private $_c = 'c';
    private $_param = array();
    private $_requestUri;

    public function __construct()
    {
    	$this->normalizeRequest();
    }
    
    protected function normalizeRequest()
    {
    	if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
    	{
    		if(isset($_GET))
    			$_GET = $this->stripSlashes($_GET);
    		if(isset($_POST))
    			$_POST = $this->stripSlashes($_POST);
    		if(isset($_REQUEST))
    			$_REQUEST = $this->stripSlashes($_REQUEST);
    		if(isset($_COOKIE))
    			$_COOKIE = $this->stripSlashes($_COOKIE);
    	}
    }
    
    public function stripSlashes(&$data)
    {
    	if(is_array($data))
    	{
    		if(count($data) == 0)
    			return $data;
    		$keys = array_map('stripslashes',array_keys($data));
    		$data = array_combine($keys,array_values($data));
    		return array_map(array($this,'stripSlashes'),$data);
    	}
    	else
    		return stripslashes($data);
    }
    
    public function getParam($name,$defaultValue=null)
    {
    	return isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : $defaultValue);
    }
    
    public function getQuery($name,$defaultValue=null)
    {
    	return isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
    }
    
    public function getPost($name,$defaultValue=null)
    {
    	return isset($_POST[$name]) ? $_POST[$name] : $defaultValue;
    }
    
    public function getRawBody()
    {
    	static $rawBody;
    	if($rawBody===null)
    		$rawBody = file_get_contents('php://input');
    	return $rawBody;
    }
    
    public function getRouter()
    {
    	$sControler = $this->getParam($this->_c);
    	if (strpos($sControler,'/')>0)
    	{
    		list($controller,$action) = explode('/', $sControler);
    		
    		if (!$controller && CApp::app()->getConf('defaultController'))
    		{
    			$controller = CApp::app()->getConf('defaultController');
    		}
    		if (!$action && CApp::app()->getConf('defaultAction'))
    		{
    			$action = CApp::app()->getConf('defaultAction');
    		}
    		return array(
    				'controller' => $controller.'Controller',
    				'action' => $action,
    		);
    	}
    	return array();
    }
    
    public function getRequestUri()
    {
    	if($this->_requestUri===null)
    	{
    		if(isset($_SERVER['HTTP_X_REWRITE_URL'])) // IIS
    			$this->_requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
    		elseif(isset($_SERVER['REQUEST_URI']))
    		{
    			$this->_requestUri = $_SERVER['REQUEST_URI'];
    			if(!empty($_SERVER['HTTP_HOST']))
    			{
    				if(strpos($this->_requestUri,$_SERVER['HTTP_HOST'])!==false)
    					$this->_requestUri = preg_replace('/^\w+:\/\/[^\/]+/','',$this->_requestUri);
    			}
    			else
    				$this->_requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i','',$this->_requestUri);
    		}
    		elseif(isset($_SERVER['ORIG_PATH_INFO']))  // IIS 5.0 CGI
    		{
    			$this->_requestUri=$_SERVER['ORIG_PATH_INFO'];
    			if(!empty($_SERVER['QUERY_STRING']))
    				$this->_requestUri.='?'.$_SERVER['QUERY_STRING'];
    		}
    		else 
    		{
    			//unable to determine the request URI
    		}
    	}
    	return $this->_requestUri;
    }
    
    public function getIsAjaxRequest()
    {
    	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
    }
}