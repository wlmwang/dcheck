<?php
/**
 * 兑换订单日志
 * @author wanglm
 * http://dcheck.uuzu.asia/index.php?c=ExchangeCollect/run
 */
class PayCollectController extends CController
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
	
	public function run($aParam)
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
		$payLog = new PayLogModel();
		$payLog->_partitionTable = date('Ym', $this->begin_ts);
		$payLog->timeline = array(array('>=',$this->begin_ts),array('<=',$this->end_ts));
		$list = $payLog->select(array('`datetime`','operator_id','`type`','channel','game_id','server_id','money_type','SUM(game_money) as game_money','SUM(u_money) as u_money','test_account'))->groupby(array('plat','operator_id','channel','game_id','server_id','money_type','test_account','`datetime`','`type`'))->findAll();
		
		
		if ($list && is_array($list)) 
		{
			foreach ($list as $v) 
			{
				if ($v) 
				{
					$payCollect = new PayCollectModel(); 
					$payCollect->plat = $this->plat;
					$payCollect->game_id = $v['game_id'];
					$payCollect->type = $v['type'];
					$payCollect->server_id = $v['server_id'];
					$payCollect->u_money = $v['u_money'];
					$payCollect->game_money = $v['game_money'];
					$payCollect->datetime = $v['datetime']; //充值时间
					$payCollect->money_type = $v['money_type'];
					$payCollect->channel = $v['channel'];
					$payCollect->operator_id = $v['operator_id'];
					$payCollect->test_account = $v['test_account'];
					
					$payCollect->_partitionTable = date("Ym",strtotime($v['datetime']));
					$payCollect->save();
				}
			}
		}
	}
	
}