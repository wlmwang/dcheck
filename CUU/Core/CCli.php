<?php
/**
 * 2014-03-06
 * @author wanglm
 *
 */
class CCli
{
	private $_c = 'c';
    private $_param = array();
    private $_requestUri;
    
    public function __construct()
    {
    	if (!empty($_SERVER['argv'])) 
    	{
    		$this->parseParam($_SERVER['argv']);
    	}
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
    
    public function getParam($name,$defaultValue=null)
    {
        return isset($this->_param[$name]) ?$this->_param[$name]:$defaultValue;
    }
    
    private function parseParam($param)
    {
    	for($i=1;$i<count($param);$i++)
    	{
    		list($key,$value) = explode('=', $param[$i]);
    		$this->_param[$key] = $value;
    	}
    }
    
}