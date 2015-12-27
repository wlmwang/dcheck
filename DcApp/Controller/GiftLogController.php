<?php
/**
 * 兑换订单日志
 * @author wanglm
 * http://dcheck.uuzu.asia/index.php?c=ExchangeLog/run
 */
class GiftLogController extends CController
{	
	protected $curl = array(
			//1		=> 'http://up.youzu.com/api/platFormData/getRebateOrder',//游族
			3 		=> 'http://api.9787.com/platform_data.php',//9787   ?action=getRebateOrder
	);
	
	protected $secret = array(
			1 =>'*(*&#^#@%platFormUMoney',
			3 => '*(*&#^#@%platFormUMoney',
	);
	
	protected $plat;	//平台
	protected $begin_ts; //开始时间
	protected $end_ts;	//结束时间
	protected $ts;		//请求时间
	protected $action;	//请求动作
	
	protected $auto=FALSE;//自动补单

	protected $timeSlice = 600;//十分钟
	
	public function run()
	{
		$this->ts = time();
		
		$this->plat = CApp::app()->tty()->getParam('plat',3);
		$this->action = CApp::app()->tty()->getParam('action','getRebateOrder');
		$this->auto = CApp::app()->tty()->getParam('auto');
		
		$this->begin_ts = strtotime(CApp::app()->tty()->getParam('begin',date('Y-m-d H:i:s',$this->ts-$this->timeSlice)));
		$this->end_ts = strtotime(CApp::app()->tty()->getParam('end',date('Y-m-d H:i:s',$this->ts)));
		
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
		$verify = md5($this->ts.$this->secret[$this->plat]);
		$url = $this->curl[$this->plat].'?action='.$this->action.'&sign='.$verify. '&time='.$this->ts.'&start_time='.$this->begin_ts.'&end_time='.$this->end_ts;
		
		$res = CPublicFunc::curlQuery($url);
		echo($res);
		if ($res) 
		{
			$res = json_decode($res, true);
			if ($res['status'] == 0)
			{
				$this->handleDB($res['data']);
			}
		}
	}

	public function handleDB($res)
	{
		if ($res)
		{
			foreach ($res as $v)
			{	
				$giftLog = new GiftLogModel();
				$giftLog->plat	= $this->plat;
				$giftLog->order_id	= $v['order_id'];
				$giftLog->account	= $v['account'];
				$giftLog->uid	= $v['uid'];
				$giftLog->vip	= $v['vip'];
				$giftLog->u_money	= $v['u_money'];
				$giftLog->rebate	= $v['rebate'];
				$giftLog->timeline	= $v['timeline'];
				$giftLog->datetime	= date('Y-m-d',$v['timeline']);
				
				$giftLog->_partitionTable = date('Ym',$v['timeline']);
				$giftLog->save();
			}
			//汇总
			$GiftCollect = new GiftCollectController();
			$aParam = array(
					'plat'=>$this->plat,
					'begin'=>$this->begin_ts,
					'end'=>$this->end_ts,
			);
			$GiftCollect->run($aParam);
		}
	}
	
	/**
	 * 判断是否是测试用户
	 * params operator_id game_id
	 */
	public function isTester($operator_id, $game_id)
	{
		$model	= new CArModel();
		$model->op_id = $operator_id;
		$model->game_id = $game_id;
		$res = $model->db('db_paycenter')->table('manage_opgame')->find();
		$array = !empty($res['test_account']) ? explode(',', $res['test_account']) : array();
		return $array;
	}
	
}