<?php
	require_once("upload_res.php");
	$res = new UploadRes();

	$data = json_decode(file_get_contents("php://input"),true);

	$base_path 	= dirname(dirname(dirname(__FILE__)));
	$target_dir = $data['target_dir'];
	$file_names = $data['file_names'];
	$status 	= 0;
	$success	= "";
	$count		= 0;

	foreach($file_names as $fn){
		$target_file = $base_path . $target_dir . $fn;
		if (file_exists($target_file)) {
			unlink($target_file);
            $status = 1;
            $count++;
		}
	}

	if($status == 0){
        $success = array('status' => "failed", "msg" => "file not exist ot failed when delete files");
	} else {
        $success = array('status' => "success", "msg" => "Deleted ".$count." of ".count($file_names)." files");
	}
	$res-> response($res->json($success), 200);

?>
