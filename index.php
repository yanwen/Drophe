<?php
require_once 'include/App.php';
App::init();

$urlinit=false;

if(! $urlinit){
  App::loader('GET /url/put','FileBaidu::GetPostURL');
  App::loader('GET /url/get','FileBaidu::GetGetURL');
  App::loader('GET /url/setacl','FileBaidu::SetACL');
  App::loader('GET /trace/size','FileBaidu::TraceSize');
  App::loader('GET /json/users','FileBaidu::ListUser');
  App::loader('GET /sendcms','CMSBaidu::Send');
	
	App::run();
}
?>
