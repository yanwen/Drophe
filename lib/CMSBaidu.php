<?php
class CMSBaidu
{
	public static function Send()
	{
		$time=App::v("t");
		if((int)$time>time()+60||(int)$time<time()-60)return;
		App::connectDb();
		$data=App::exec('R','dropheusers',array(),"ak='".App::v("ak")."'");
        if($data){
			$sk=$data[0]["sk"];
			
			$cmscount=$data[0]["cmscount"];
			$cmscount=(int)$cmscount;
			
			$usedcmscount=$data[0]["usedcmscount"];
			$usedcmscount=(int)$usedcmscount;
				
			$sign=md5($time.'-'.$sk);
			if($sign==App::v("sign")){
			
			$cusers=App::execRead('dropheusers','','sum(usedcmscount) as c');
			if($cusers){
				if((int)($cusers[0]["c"])>=500)
				{
					echo 'error';
					return;
				}
			}
			
				App::importPage('bcmssdk/bcms.class.php');
				$client_id = 'xxx';//百度ak
				$client_secret = 'xxx';//百度sk
				$url = 'bcms.api.duapp.com';
				$bms = new BaiduBcms($client_id, $client_secret, $url, '324b11935f09ac7b7c3d4d1a5814f61a');
				$bms->set_return_errorcode(true);
				
				if($usedcmscount<$cmscount-1)
					$opt[BaiduBcms::MESSAGE] = App::v("msg");
				else
					$opt[BaiduBcms::MESSAGE] = "#即将超过月短信配额#".App::v("msg");
					
				$opt[BaiduBcms::ADDRESS] = '["'.App::v("to").'"]';
				
				if($usedcmscount<$cmscount)
				$ret = $bms->sms($opt);
				else{
					echo 'error';
					return;
				}
				
				App::exec('U','dropheusers',array("usedcmscount"=>($usedcmscount+1)),"ak='".App::v("ak")."'");
				
				echo 'success';
				return;
			}
		}
		App::closeDb();
		echo 'error';
		return;
	}
}
?>