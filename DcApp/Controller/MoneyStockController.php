<?php
/**
 * UB存量(按天维度)
 * @author wanglm
 * 
 * http://dcheck.uuzu.asia/index.php?c=MoneyStock/run&date=2014-03-10&plat=1
 *
 */
class MoneyStockController extends CController
{	
	protected $curl = array(
        1 => 'http://up.youzu.com/api/platFormData/getUMoney',//uuzu[new]
        3 => 'http://api.9787.com/platform_data.php?action=getUMoney',//9787[new]
	);
	
	protected $platForm = array(
		1	=> 'youzu',
		3	=> '9787'
	);
	protected $secret = array(
			1 => '*(*&#^#@%platFormUMoney',
			3 => '*(*&#^#@%platFormUMoney',
	);
	
	protected $ts;		//请求时间
	protected $plat;	//平台
	protected $begin_ts; //开始时间
	protected $end_ts;	//结束时间
	
	protected $date;	//汇总时间20140317
	public function run()
	{
		$this->ts = time();
		
		$this->plat = CApp::app()->tty()->getParam('plat',1);

		$this->begin_ts = strtotime(CApp::app()->tty()->getParam('begin',date('Ymd',strtotime("-1 day")).' 00:00:00'));
		$this->end_ts = strtotime(CApp::app()->tty()->getParam('end',date('Ymd',strtotime("-1 day")).' 23:59:59'));
		
		if (!array_key_exists($this->plat, $this->curl))
		{
			die('Error plat!');
		}
		
		$this->handleData();
	}
	
	public function handleData()
	{
		$verify = md5($this->ts.$this->secret[$this->plat]);
		
		$url = $this->curl[$this->plat].'&time='.$this->ts.'&start_time='.$this->begin_ts.'&end_time='.$this->end_ts.'&sign='.$verify;
		$res = CPublicFunc::curlQuery($url);
		var_dump($res);
		if ($res) 
		{
			$res = json_decode($res, true);
			if ($res['status'] == 0 && !empty($res['number']))
			{
				$this->handleDB($res['data']);
			}
		}
	}
	
	public function handleDB($res)
	{
		foreach ($res as $v)
		{
			$moneyStock = new MoneyStockModel();
			$moneyStock->plat	 = $this->plat;
			$moneyStock->datetime= date('Y-m-d',strtotime($v['date']));
			$moneyStock->recordtime	 = $v['record_time'];
			$moneyStock->stock	 = $v['left'];
			$moneyStock->pay	 = $v['charge'];
			$moneyStock->exchange= $v['exchange'];
			$moneyStock->gift	 = $v['gift'];
			$moneyStock->save();
		}
	}
	
}