<?php
/**
 * 2014-03-10
 * @author wanglm
 *
 */
class CPublicFunc 
{

	public static function mycrypt($data, $key)
	{
		$sign = '';
		switch (strtolower(gettype($data)))
		{
			case 'integer':
			case 'double':
			case 'string':
				$sign = $data;
				break;
			case 'array':
				$sign = http_build_query($data);
				break;
			default:
				return '';
		}
		$sign = md5($sign . $key);
		return $sign;
	}
	
    public static function getIP() 
    {
	  if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
		$ip = getenv("HTTP_CLIENT_IP");
	  else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
	  {
		$ip = getenv("HTTP_X_FORWARDED_FOR");
		$ip = explode(',', $ip);
		$ip = $ip[0];
		$ip = trim($ip);
	  } 
	  else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
		$ip = getenv("REMOTE_ADDR");
	  else if (isset($_SERVER ['REMOTE_ADDR']) && $_SERVER ['REMOTE_ADDR'] && strcasecmp($_SERVER ['REMOTE_ADDR'], "unknown"))
		$ip = $_SERVER ['REMOTE_ADDR'];
	  else
		$ip = "unknown";
	  return ($ip);
    }

    public static function curlQuery($url, $postTag = false, $postData = array()) 
    {
	  $ch = curl_init();
	  curl_setopt($ch, CURLOPT_URL, $url);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	  if ($postTag)
	  {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
	  }
	  $result = curl_exec($ch);
	  curl_close($ch);
	  return $result;
    }

    public static function changeSecondToView($second) 
    {
	  $floorHour = floor($second / 3600);
	  $remain = $second % 3600;
	  $hour = $floorHour == 0 ? '00' : $floorHour;
	  $floorMin = floor($remain / 60);
	  $remain = $remain % 60;
	  $min = strlen($floorMin) == 1 ? '0' . $floorMin : $floorMin;
	  $second = strlen($remain) == 1 ? '0' . $remain : $remain;
	  return $hour . ':' . $min . ':' . $second;
    }

    //生成十八位的随机数字
    //PublicFunHelper::getActivationString();
    public static function getActivationString($length = 18) 
    {
	  PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
	  $rand_str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

	  $len = strlen($rand_str) - 1;
	  $key = "";

	  for ($i = 0; $i < $length; $i++)
	  {
		$key .= $rand_str[mt_rand(0, $len)];
	  }
	  return $key;
    }

    //得到分表求余
    //PublicFunHelper::getCrcDividend($val);
    public static function getCrcDividend($val, $dividend = 100) 
    {
	  $crc = sprintf("%u", crc32($val));
	  return fmod($crc, $dividend);
    }

}