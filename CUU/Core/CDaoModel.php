<?php
/**
 * 2014-03-09
 * @author wanglm
 *
 */
class CDaoModel extends CModel
{
	public function __construct()
	{
		$_dbConf = CApp::app()->getConf('Db');
		foreach ($_dbConf as $identify=>$conf)
		{
			$this->_Db[$identify] = CDb::createDb($conf['host'], $conf['user'], $conf['pwd'],$conf['dbName']);
		}
	}
	
	public function find(){}
	
	public function insert(){}
	
	public function update(){}
	
	public function delete(){}
	
	
}

?>