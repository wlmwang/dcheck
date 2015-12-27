<?php
/**
 * 2014-03-06
 * @author wanglm
 *
 */
class CFactory
{
	private static $_factory = array();
	
	/**
	 * Single Facory
	 */
	public static function SFactory($name)
	{
		if (!empty(self::$_factory[$name]))
		{
			return self::$_factory[$name];
		}
		return self::$_factory[$name] = class_exists($name) ?new $name() :NULL;
	}
	
	/**
	 * 
	 * @param unknown $controller
	 * @param unknown $app
	 * @return multitype:|Ambigous <unknown, NULL>
	 */
	public static function createCController($controller,$app)
	{
		if (!empty(self::$_factory[$controller]))
		{
			return self::$_factory[$controller];
		}
		return self::$_factory[$controller] = class_exists($controller) ?new $controller($app) :NULL;
	}
}
