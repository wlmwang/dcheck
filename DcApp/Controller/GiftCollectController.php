<?php
/**
 * 兑换订单日志
 * @author wanglm
 * http://dcheck.uuzu.asia/index.php?c=ExchangeCollect/run
 */
class GiftCollectController extends CController
{
	protected $curl = array(
			3 		=> '',//9787
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
		}
		
		if (!array_key_exists($this->plat, $this->curl))
		{
			die('Error plat!');
		}
		
		$this->handleData();
		
	}
	
	public function handleData()
	{
		
		$giftLog = new GiftLogModel();
		$giftLog->_partitionTable = date('Ym', $this->begin_ts);
		$giftLog->timeline = array(array('>=',$this->begin_ts),array('<=',$this->end_ts));
		$list = $giftLog->select(array('`datetime`','SUM(rebate) as rebate','SUM(u_money) as u_money'))->groupby(array('plat','`datetime`'))->findAll();
		
		if ($list && is_array($list)) 
		{
			foreach ($list as $v) 
			{
				if ($v) 
				{
					$giftCollect = new GiftCollectModel(); 
					$giftCollect->plat	= $this->plat;
					$giftCollect->u_money = $v['u_money'];
					$giftCollect->rebate = $v['rebate'];
					$giftCollect->datetime = $v['datetime']; //充值时间
					
					$giftCollect->_partitionTable = date("Ym",strtotime($v['datetime']));
					$giftCollect->save();
				}
			}
		}
	}
	
}