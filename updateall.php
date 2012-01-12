<?php
require_once 'include/App.php';
App::init();
require_once 'lib/FileBaidu.php';
FileBaidu::UpdateUsedSpace();
?>