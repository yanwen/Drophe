<?php
function bs_log($log) {
	//trigger_error ( basename ( __FILE__ ) . " [time: " . time () . "][LOG: $log]" );
}

class FileBaidu
{
        public static function GetPostURL()
        {
                      $type=App::v("type");
                      if($type!="private")$type="public";
        	$time=App::v("t");
                if((int)$time>time()+60||(int)$time<time()-60)return;
        	App::connectDb();
        	$data=App::exec('R','dropheusers',array(),"ak='".App::v("ak")."'");
          //echo 'aaa';
                if($data){
                  if((int)($data[0]["usedspace"])>(int)($data[0]["space"])){echo 'error';return;}
                      $host = 'bcs.duapp.com';
                      $bucket = "drophe";
               	      $object = "/".App::v("ak")."/".$type."/".App::v("o");
                      $sk=$data[0]["sk"];
                      $sign=md5(App::v("o").'-'.$time.'-'.$sk);
                  //echo '|'.$sign;
                      $opt = array (
                        "time" => time ()+3600
                      );
                      if($sign==App::v("sign")){
                          App::importPage('bdsdk/bcs.class.php');
                          $baidu_bcs = new BaiduBCS();
                          $url=$baidu_bcs->generate_post_object_url ( $bucket, $object, $opt );
                          echo $url;
                          return;
                      }
                  //echo $sign;
                }
        	App::closeDb();
                echo "error";
        }
        
        public static function GetGetURL()
        {
                $type=App::v("type");
                if($type!="private")$type="public";
        	$time=App::v("t");
          //echo time();
                if($type=="private"){
                  if((int)$time>time()+60||(int)$time<time()-60)return;
                }
                
        	App::connectDb();
        	$data=App::exec('R','dropheusers',array(),"ak='".App::v("ak")."'");
                $object = "/".App::v("ak")."/".$type."/".App::v("o");
                if($data){
                      App::importPage('bdsdk/bcs.class.php');
                      $baidu_bcs = new BaiduBCS();
                      
		      $sk=$data[0]["sk"];
                      $bucket = "drophe";
                      
                      $opt = array (
                        "time" => time ()+3600
                      );
                          
                      if($type=="public"){
					  $opt = array ();
                            $url= $baidu_bcs->generate_get_object_url ( $bucket, $object, $opt );
                            //header("location:".$url);
							echo $url;
                            return;
                      }
                      
                      $uak=App::v("uak");
                      $data=App::exec('R','dropheaclusers',array(),"userak='".$uak."' and object='".$object."'");
                      
                      if($data){
                          $usk=$data[0]["usersk"];
                          $reurl=$data[0]["reurl"];
                          $host = 'bcs.duapp.com';
                          
                          $time=App::v("t");
                          
                          $sign=md5(App::v("o").'-'.$time.'-'.$sk.'-'.$usk);
                          
                          if($sign==App::v("sign")){
                              $url= $baidu_bcs->generate_get_object_url ( $bucket, $object, $opt );
                              //header("location:".$url);
			      echo $url;
                              return;
                          }else{
                              //echo $sign;
                              //header("location:".$reurl);
							  echo $url;
                              return;
                          }
                      }
                }
                
        	App::closeDb();
          	
                echo 'error';
                return;
        }
        
        public static function SetACL()
        {
        	$time=App::v("t");
          //echo time();
                if((int)$time>time()+60||(int)$time<time()-60)return;
        	App::connectDb();
                $data=App::exec('R','dropheusers',array(),"ak='".App::v("ak")."'");
                if($data){
                  	$object = "/".App::v("ak")."/"."private"."/".App::v("o");
                        $sk=$data[0]["sk"];
                        $sign=md5(App::v("o").'-'.$time.'-'.$sk);
                  //echo $sign;
                        if($sign==App::v("sign")){
                          App::exec('D','dropheaclusers',array(),"object='".$object."' and userak='".App::v("uak")."'");
                          if(App::v("public")!="true"){
                            App::exec('C','dropheaclusers',array("userak"=>App::v("uak"),"usersk"=>App::v("usk"),"object"=>$object,"reurl"=>App::v("reurl")));
                          }
                          return;
                        }
                }
                App::closeDb();
                
                echo 'error';
                return;
        }
        
        public static function TraceSize()
        {
		App::connectDb();
			$data=App::execRead('dropheusers','','sum(cmscount) as c');
			if($data)echo $data[0]["c"];
          echo self::GetSize();
		  App::closeDb();
        }
        
        public static function GetSize()
        {
                $time=App::v("t");
          //echo time();
          	if((int)$time>time()+60||(int)$time<time()-60)return;
                
                $data=App::exec('R','dropheusers',array(),"ak='".App::v("ak")."'");
                if($data){
                  $sk=$data[0]["sk"];
                  $sign=md5($time.'-'.$sk);
                  //echo $sign;
                  //return;
                  if($sign==App::v("sign")){
                    $opt = array ('start' => 0, 'limit' => 200, 'prefix' => '/' );
                    $size=0;
                    $count=200;
                    while($count==200){
                      $bucket = "drophe";
                      App::importPage('bdsdk/bcs.class.php');
                      $baidu_bcs = new BaiduBCS();
                      $response = $baidu_bcs->list_object_by_dir ($bucket,'/'.App::v("ak").'/',2,$opt);
                      $list=json_decode($response->body);
                      $count=(float)($list->object_total);
                      for($i=0;$i<$count;$i++){
                      	$size+=(float)($list->object_list[$i]->size);
                      }
                    }
                    return $size;
                  }
                }
                
                
                return 0;
        }
        
        public static function GetUserSize($dir)
        {
                $opt = array ('start' => 0, 'limit' => 200, 'prefix' => '/' );
                $size=0;
                $count=200;
                while($count==200){
                  $bucket = "drophe";
                  App::importPage('bdsdk/bcs.class.php');
                  $baidu_bcs = new BaiduBCS();
                  $response = $baidu_bcs->list_object_by_dir ($bucket,$dir,2,$opt);
                  $list=json_decode($response->body);
                  $count=(float)($list->object_total);
                  for($i=0;$i<$count;$i++){
                    if($list->object_list[$i]->is_dir=="1")$size+=self::GetUserSize($list->object_list[$i]->object);
                    else $size+=(float)($list->object_list[$i]->size);
                  }
                }
                return $size;
        }
        
        public static function ListUser()
        {
        	App::importPage('bdsdk/bcs.class.php');
                $baidu_bcs = new BaiduBCS();
        	App::connectDb();
          	$data=App::exec('R','dropheusers',array(),"1=1");
                $users=array();
                foreach($data as $user)
                {
                  //self::GetUserSize('/'.$user["ak"].'/')
                  $users[count($users)]=array("userid"=>$user["userid"],"space"=>(float)$user["space"],"used"=>$user["usedspace"],"cmscount"=>$user["cmscount"],"usedcmscount"=>$user["usedcmscount"]);
                }
                echo json_encode($users);
                App::closeDb();
        }
        
        public static function UpdateUsedSpace()
        {
        	App::importPage('bdsdk/bcs.class.php');
                $baidu_bcs = new BaiduBCS();
        	App::connectDb();
                $data=App::exec('R','dropheusers',array(),"1=1");
                foreach($data as $user)
                {
                	App::exec('U','dropheusers',array("usedspace"=>self::GetUserSize('/'.$user["ak"].'/')),"userid='".$user["userid"]."'");
                }
                App::closeDb();
        }

	
}
?>