<?php

/**
 * 时区
 */
date_default_timezone_set('Asia/Shanghai');

/**
 * 全局设置
 */
error_reporting(E_ALL);

/**
 * 项目设置
 */
define('__APP__',str_replace(array('//','\\'),array('/','/'),dirname(__FILE__)));
require __APP__.'/../CUU/CApp.php';

CApp::createCApp()->run(include_once(__APP__.'/Conf/Conf.php'));
