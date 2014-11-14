<?php
/*
	This file is part of DB Backup.
*/
if( !isset( $_REQUEST['file'] ) && empty( $_REQUEST['file'] ) ){
	die('Please do not load this file directly.');
}
$filePath = $_REQUEST['file'];
$fileInfo = pathinfo($filePath);
$fileName = $fileInfo['basename'];
$fileArr = format_fileName( $fileName );
$fileName = $fileArr['fileName'].'.'.$fileArr['fileExt'];
ob_start();
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename='.basename($fileName));
ob_clean();
flush();
readfile( $filePath );
unlink( '../../uploads/'.$fileInfo['basename'] );
die;
// action to make proper file name
function format_fileName( $fileName ){
	$array = array();
	$tmpArr = explode('.', $fileName);
	$index = count( $tmpArr ) - 1;
	$array['fileExt'] = $tmpArr[$index];
	$tmpArr = explode('_', $fileName);
	$index = count( $tmpArr ) - 1;	
	$array['fileName'] = str_replace('_'.$tmpArr[$index], '', $fileName);
	return $array;
}
?>