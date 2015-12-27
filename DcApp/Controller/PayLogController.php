<?php
/**
 * 兑换订单日志
 * @author wanglm
 * http://dcheck.uuzu.asia/index.php?c=ExchangeLog/run
 */
class PayLogController extends CController
{	
	protected $curl = array(
			1		=> 'http://passport.youzu.com/payment/GetOrderLog',//游族
			3 		=> 'http://open.9787.com/?c=order&a=pay',//9787
			590		=> 'http://accounts.gtarcade.com/sync/GetOrderLog', //北美
	);
	
	protected $secret = array(
			1 =>'!@#$%^&*()',
			590 =>'!@#$%^&*()',
			3 => 'P6Xqr9#TL@pNc6T7',
	);
	
	protected $plat;	//平台
	protected $begin_ts; //开始时间
	protected $end_ts;	//结束时间
	protected $ts;		//请求时间
	protected $action;	//请求动作
	
	protected $game_id; //游戏
	protected $channel; //渠道
	
	protected $cfg;//获取pay方式
	
	protected $auto=FALSE;//自动补单
	
	protected $timeSlice = 600;//十分钟
	
	public function run()
	{
		$this->ts = time();
		
		$this->plat = CApp::app()->tty()->getParam('plat',1);
		$this->action = CApp::app()->tty()->getParam('action','pay');
		
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

		$this->game_id = CApp::app()->tty()->getParam('game_id');
		
		$this->channel = CApp::app()->tty()->getParam('channel');
		
		$this->cfg = $this->getChannel(); //获取充值方式
		
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
				
				$PayStock = new PayLogModel();
				$PayStock->plat	= $this->plat;
				$PayStock->order_id	= $v['id'];
				$PayStock->type	= isset($v['type'])?$v['type']:0; //(是否直充) type=1 直冲 ， type=2 兑换 ,type=0 未知平台
				$PayStock->game_id	= $v['game_id'];
				$PayStock->operator_id= isset($v['op_id'])?$v['op_id']:0;
				$PayStock->server_id	= $v['server_id'];
				$PayStock->u_money	= $v['u_money'];
				$PayStock->money_type	= $v['currency_id'];//货币种类 currency_id 1 人民币 2 美元
				$PayStock->game_money	= isset($v['game_money'])?$v['game_money']:0;
				$PayStock->timeline	= $v['timeline'];
				$PayStock->datetime	= date('Y-m-d',$v['timeline']);
				$PayStock->account	= $v['account'];
				$PayStock->channel	= isset($v['channel'])?$v['channel']:0;
				$PayStock->extra	= NULL;
				
				if (in_array($this->plat,array(1,590,10000)))
				{
					//判断是否是测试帐号
					$test_account = $this->isTester(intval($PayStock->operator_id), intval($PayStock->game_id));
					$PayStock->test_account = ($test_account && in_array($PayStock->account, $test_account)) ? 1 : 0;
					//end
					//获取充值方式
					foreach ($this->cfg as $key => $value)
					{
						if (!empty($value['uuzu']) && is_array($value['uuzu']))
						{
							if (in_array($PayStock->channel, $value['uuzu']))
							{
								$PayStock->is_channel = $key;
								break;
							}
						}
						else
						{
							if (!empty($value['uuzu']) && $value['uuzu'] == $PayStock->channel)
							{
								$PayStock->is_channel = $key;
								break;
							}
						}
					}
					//end
				}
				elseif ($this->plat == 3)
				{
					$PayStock->test_account	= $v['is_test'];
					//获取充值方式
					foreach ($this->cfg as $key => $value)
					{
						if (isset($value['9787']) && ($value['9787'] == $PayStock->channel))
						{
							$PayStock->is_channel = $key;
							break;
						}
					}
					//end
				}
				
				$PayStock->_partitionTable = date('Ym',$v['timeline']);
				$PayStock->save();
			}
			
			//汇总
			$payCollect = new PayCollectController();
			$aParam = array(
					'plat'=>$this->plat,
					'begin'=>$this->begin_ts,
					'end'=>$this->end_ts,
			);
			$payCollect->run($aParam);
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
	
	/**
	 * 获取支付方式id数
	 * 充值渠道        uuzu        9787        数据中心
	 * 支付宝网上银行    1        3              100
	 * 快钱银行卡        2                       101
	 * 易宝银行卡        3        2              102
	 * 支付宝            4        4              103
	 * 易宝神州行卡      5                       104
	 * 神州付            6,7,17   5              105
	 * 全国电话手机V币   8        6              106
	 * 快钱神州行        9                       107
	 * 易宝盛大卡        10                      108
	 * U 币              11                      109
	 * 俊卡              12        密切关注      110
	 * 手机短信充值      13        7             111
	 * ChinaBank神州行卡 14                      112
	 * 征途卡            15                      113
	 * 短信支付          18                      114
	 * 盈华讯方短信充值  19                      115
	 * 易宝征途卡        20                      116
	 * Paypal支付        22                      117
	 * 固定电话充值      23                      118
	 * 人工汇款充值      24                      119
	 * 盛大卡(盛付通）   25                      120
	 * 支付宝无线        26                      121
	 * 神州付无线        27                      122
	 * 财付通            28        10            123
	 * 财付通无线        29                      124
	 * U币兑换           30                      125
	 * mo9无线支付       31                      126
	 * 支付宝国际卡      32                      127
	 * TrialPay          33                      128
	 * 易联无线支付      34                      129
	 * playSpan          35                      130
	 * 金豆                       1              131
	 * @param  参数名
	 * @return array
	 */
	public function getChannel()
	{
		$config = array(
				100 => array('uuzu' => 1, '9787' => 3),
				101 => array('uuzu' => 2),
				102 => array('uuzu' => 3, '9787' => 2),
				103 => array('uuzu' => 4, '9787' => 4),
				104 => array('uuzu' => 5),
				105 => array('uuzu' => array(6, 7, 17), '9787' => 5),
				106 => array('uuzu' => 8, '9787' => 6),
				107 => array('uuzu' => 9),
				108 => array('uuzu' => 10),
				109 => array('uuzu' => 11),
				110 => array('uuzu' => 12),
				111 => array('uuzu' => 13, '9787' => 7),
				112 => array('uuzu' => 14),
				113 => array('uuzu' => 15),
				114 => array('uuzu' => 18),
				115 => array('uuzu' => 19),
				116 => array('uuzu' => 20),
				117 => array('uuzu' => 22),
				118 => array('uuzu' => 23),
				119 => array('uuzu' => 24),
				120 => array('uuzu' => 25),
				121 => array('uuzu' => 26),
				122 => array('uuzu' => 27),
				123 => array('uuzu' => 28, '9787' => 10),
				124 => array('uuzu' => 29),
				125 => array('uuzu' => 30),
				126 => array('uuzu' => 31),
				127 => array('uuzu' => 32),
				128 => array('uuzu' => 33),
				129 => array('uuzu' => 34),
				130 => array('uuzu' => 35),
				131 => array('9787' => 1),
		);
	
		return $config;
	}
	
}