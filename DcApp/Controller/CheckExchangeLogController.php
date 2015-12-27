<?php
/**
 * 兑换订单日志
 * @author wanglm
 * http://dcheck.uuzu.asia/index.php?c=CheckExchangeLog/run
 */
class CheckExchangeLogController extends CController
{	
	protected $curl = array(
			1		=> 'http://passport.youzu.com/payment/CheckOrderLog',//游族
			3 		=> 'http://open.9787.com/?c=order&a=exchange',//9787
			590		=> 'http://accounts.gtarcade.com/sync/CheckOrderLog', //北美
			10000	=> 'http://mp.uuzu.com/api/CheckOrderLog', //手游
	);
	protected $secret = array(
			1 =>'!@#$%^&*()',
			590 =>'!@#$%^&*()',
			10000 =>'!@#$%^&*()',
			3 => 'P6Xqr9#TL@pNc6T7',
	);
	
	protected $plat;	//平台
	protected $begin_ts; //开始时间
	protected $end_ts;	//结束时间
	protected $ts;		//请求时间
	protected $action;	//请求动作
	
	protected $game_id; //游戏
	protected $op_id; 	//运营商
	protected $channel; //渠道
	
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
		
		//不能跨天   否则取当天时间
		if (date('Ymd', $this->begin_ts) != date('Ymd', $this->end_ts)) 
		{
			$this->end_ts = strtotime(date('Ymd', $this->begin_ts) . ' 23:59:59');
			$this->flag = false;
		}
		
		$this->op_id = CApp::app()->tty()->getParam('op_id');
		
		$this->channel = CApp::app()->tty()->getParam('channel');
		
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
			if ($this->channel)
			{
				$url .= '&channel='.$this->channel;
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
			if ($this->channel)
			{
				$params['channel'] = $this->channel;
			}
			$auth = base64_encode(http_build_query($params));
			$verify = md5($auth.$this->secret[$this->plat]);
			$url = $this->curl[$this->plat] . '&auth='.$auth.'&verify=' . $verify;
		}
		$res = CPublicFunc::curlQuery($url);
		echo ($res);
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
				
				$checkMoneyStock = new CheckExchangeLogModel();
				$checkMoneyStock->plat	= $this->plat;
				$checkMoneyStock->game_id	= empty($v['game_id']) ? 0 : $v['game_id'];
				$checkMoneyStock->operator_id= isset($v['op_id']) ? $v['op_id'] : $this->plat;
				$checkMoneyStock->server_id	= $v['server_id'];
				$checkMoneyStock->u_money	= $v['u_money'];
				$checkMoneyStock->game_money	= $v['game_money'];
				$checkMoneyStock->datetime	= date('Y-m-d', $this->begin_ts);
				$checkMoneyStock->type		= $v['type']; //(是否直充)

				$checkMoneyStock->_partitionTable = date('Ym', $this->begin_ts);
				$checkMoneyStock->save();
			}
		}
	}
	
}