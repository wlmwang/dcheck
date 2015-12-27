<?php
/**
 * 兑换订单日志
 * @author wanglm
 * http://dcheck.uuzu.asia/index.php?c=ExchangeCollect/run
 */
class ExchangeCollectController extends CController
{
	protected $curl = array(
			1		=> '',//游族
			3 		=> '',//9787
			590		=> '', //北美
			10000	=> '', //手游
	);
	
	protected $plat;	//平台
	protected $begin_ts; //开始时间
	protected $end_ts;	//结束时间
	protected $ts;		//请求时间
	
	protected $auto=FALSE;//自动补单
	
	protected $timeSlice = 600;//十分钟
	
	public function run($aParam=NULL)
	{
		$this->ts = time();
		
		if ($aParam==NULL) 
		{
			$this->plat = CApp::app()->tty()->getParam('plat',1);
			$this->auto = CApp::app()->tty()->getParam('auto');
			
			$this->begin_ts = strtotime(CApp::app()->tty()->getParam('begin',date('Y-m-d H:i:s',$this->ts-$this->timeSlice)));
			$this->end_ts = strtotime(CApp::app()->tty()->getParam('end',date('Y-m-d H:i:s',$this->ts)));
		}
		else 
		{
			$this->plat = $aParam['plat'];
			$this->begin_ts = $aParam['begin'];
			$this->end_ts = $aParam['end'];

			$this->auto = 0;
		}
		
		//自动补数据（补单）
		if ($this->auto) 
		{
			$this->begin_ts = strtotime(date('Ymd',strtotime("-1 day")).' 00:00:00');
			$this->end_ts = strtotime(date('Ymd',strtotime("-1 day")).' 23:59:59');
		}
		
		//不能跨月   否则取当天时间
		if (date('Ym', $this->begin_ts) != date('Ym', $this->end_ts)) 
		{
			$this->end_ts = strtotime(date('Ymd', $this->begin_ts) . ' 23:59:59');
			$this->flag = false;
		}
		
		if (!array_key_exists($this->plat, $this->curl))
		{
			die('Error plat!');
		}
		
		$this->handleData();
		
	}
	
	public function handleData()
	{
		
		$exchangeLog = new ExchangeLogModel();
		$exchangeLog->_partitionTable = date('Ym', $this->begin_ts);
		$exchangeLog->timeline = array(array('>=',$this->begin_ts),array('<=',$this->end_ts));
		$list = $exchangeLog->select(array('`datetime`','operator_id','game_id','server_id','`type`','SUM(game_money) as game_money','SUM(u_money) as u_money','test_account','money_type'))->groupby(array('plat','game_id','type','server_id','operator_id','test_account','`datetime`','`money_type`'))->findAll();
		
		if ($list && is_array($list)) 
		{
			foreach ($list as $v) 
			{
				if ($v) 
				{
					$exchangeCollect = new ExchangeCollectModel(); 
					$exchangeCollect->plat	= $this->plat;
					$exchangeCollect->game_id = $v['game_id'];
					$exchangeCollect->server_id = $v['server_id'];
					$exchangeCollect->u_money = $v['u_money'];
					$exchangeCollect->game_money = $v['game_money'];
					$exchangeCollect->datetime = $v['datetime']; //充值时间
					$exchangeCollect->type = $v['type'];
					$exchangeCollect->money_type = $v['money_type'];
					$exchangeCollect->operator_id = $v['operator_id'];
					$exchangeCollect->test_account = $v['test_account'];
					
					$exchangeCollect->_partitionTable = date("Ym",strtotime($v['datetime']));
					$exchangeCollect->save();
				}
			}
		}
	}
	
}