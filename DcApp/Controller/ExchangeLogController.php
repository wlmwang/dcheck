<?php
/**
 * 兑换订单日志
 * @author wanglm
 */
class ExchangeLogController extends CController
{	
	protected $curl = array(
		1	=> 'http://localhost/payment/GetOrderLog',
	);
	
	protected $secret = array(
		1 =>'!@#$%^&*()',
	);
	
	protected $plat;	//平台
	protected $begin_ts; 	//开始时间
	protected $end_ts;	//结束时间
	protected $ts;		//请求时间
	protected $action;	//请求动作
	
	protected $game_id; //游戏
	protected $op_id; 	//运营商
	
	protected $auto=FALSE;//自动补单

	protected $timeSlice = 600;//十分钟
	
	public function run()
	{
		$this->ts = time();
		
		$this->plat = CApp::app()->tty()->getParam('plat',1);
		$this->action = CApp::app()->tty()->getParam('action','exchange');
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
			$this->flag = false;
		}

		/*
		if ($this->action == 'union_exchange')
		{
			$this->op_id = CApp::app()->tty()->getParam('op_id');
		}
		*/
		
		if (!array_key_exists($this->plat, $this->curl))
		{
			die('Error plat!');
		}
		
		$this->handleData();
		
	}
	
	public function handleData()
	{
		if (in_array($this->plat,array(1,590,10000)))
		{
			$verify = md5($this->ts.$this->secret[$this->plat]);
			$url = $this->curl[$this->plat].'?action='.$this->action.'&sign='.$verify. '&timeline='.$this->ts.'&start_time='.$this->begin_ts.'&end_time='.$this->end_ts;
			if ($this->game_id)
			{
				$url .= '&game_id='.$this->game_id;
			}
			if ($this->op_id)
			{
				$url .= '&op_id='.$this->op_id;
			}
		}
		elseif ($this->plat == 3)
		{
			$params = array(
					'start_time' => $this->begin_ts,
					'end_time' => $this->end_ts,
			);
			if ($this->game_id)
			{
				$params['game_id'] = $this->game_id;
			}
			if ($this->op_id)
			{
				$params['op_id'] = $this->op_id;
			}
			$auth = base64_encode(http_build_query($params));
			$verify = md5($auth.$this->secret[$this->plat]);
			$url = $this->curl[$this->plat] . '&auth='.$auth.'&verify=' . $verify;
		}
		$res = CPublicFunc::curlQuery($url);
		echo($res);
		if ($res) 
		{
			$res = json_decode($res, true);
			if ($res['status'] == 1)
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
				if ($this->plat == 3)
				{
					$v['id'] = $v['order_id'];
				}
				
				$exchangeLog = new ExchangeLogModel();
				$exchangeLog->plat	= $this->plat;
				$exchangeLog->order_id	= $v['id'];
				$exchangeLog->game_id	= $v['game_id'];
				$exchangeLog->operator_id= isset($v['op_id'])?$v['op_id']:0;
				$exchangeLog->server_id	= $v['server_id'];
				$exchangeLog->u_money	= $v['u_money'];
				$exchangeLog->game_money	= $v['game_money'];
				$exchangeLog->timeline	= $v['timeline'];
				$exchangeLog->datetime	= date('Y-m-d',$v['timeline']);
				$exchangeLog->account	= $v['account'];
				$exchangeLog->type	= isset($v['type'])?$v['type']:0; //(是否直充) type=1 直冲 ， type=2 兑换 ,type=0 未知平台
				$exchangeLog->money_type	= isset($v['currency_id'])?$v['currency_id']:0;//货币种类 currency_id 1 人民币 2 美元
				$exchangeLog->extra	= NULL;
				//判断是否是测试帐号
				$test_account = $this->isTester(intval($exchangeLog->operator_id), intval($exchangeLog->game_id));
				$exchangeLog->test_account = ($test_account && in_array($exchangeLog->account, $test_account)) ? 1 : 0;
				//end
				
				$exchangeLog->_partitionTable = date('Ym',$v['timeline']);
				$exchangeLog->save();
			}
			//汇总
			$exchangeCollect = new ExchangeCollectController();
			$aParam = array(
					'plat'=>$this->plat,
					'begin'=>$this->begin_ts,
					'end'=>$this->end_ts,
			);
			$exchangeCollect->run($aParam);
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
