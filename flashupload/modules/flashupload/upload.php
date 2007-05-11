<?php

include_once( "kernel/classes/ezcontentupload.php" );
include_once( "lib/ezutils/classes/ezdebug.php" );
include_once( "lib/ezutils/classes/ezhttptool.php" );
include_once( 'lib/ezutils/classes/ezexecution.php' );
include_once( 'lib/ezutils/classes/ezsession.php' );
include_once( 'lib/ezutils/classes/ezsys.php' );
include_once('kernel/classes/datatypes/ezuser/ezuser.php');

$module =& $Params["Module"];

$http = new eZHTTPTool();

	$handle = fopen('/home/thewaiti/www/var/upload.log', 'a+');
	fwrite($handle, 'SERVER=' . $_SERVER['REQUEST_URI'] . "\n");
// if anon
//    get sessid
//    session red
//    get new user
//    logincurrent
//$user = eZUser::currentUser();
	$sessname = session_name();
	fwrite($handle, 'hostname=' . eZSys::hostname() . "\n");
	fwrite($handle, 'sessname=' . $sessname . "\n");
//if ( $user->isAnonymous() && $http->hasGetVariable( $sessname ) )
//{
	
	fwrite($handle, 'currentsessname=' . session_name() . "\n");
	$sessid = $http->getVariable( $sessname );
	fwrite($handle, 'sessid=' . $sessid . "\n");
	//$sess = eZSessionRead( $sessid );
//	$userID = $GLOBALS['eZSessionUserID'];
	fwrite($handle, 'userID=' . $userID . "\n");
//	$user = eZUser::instance( $userID );
//	$user->loginCurrent();
//}
	

$parent = $Params['NodeID'];
//$pos = strpos( $parent, '?');
//if ( $pos !== false )
//	$parent = substr( $parent, 0, $pos );

$upload = new eZContentUpload();
$upload->handleUpload( &$result, "Filedata", $parent, false );

//$handle = fopen('/home/thewaiti/www/var/upload.log', 'a+');
//fwrite($handle, 'var_export=' . var_export($result, true) . "\n");
//fclose($handle);


// old-skool form upload
if ( $http->hasPostVariable('RedirectURL') )
{
	$module->redirectTo( $http->postVariable('RedirectURL') );
}
else
{
		fwrite($handle, 'user=' . var_export($user, true) . "\n");
	// can't return values to flash so use http codes
	if ( count( $result['errors'] ) > 0 )
	{
		$status = '';
		$message = $result['errors'][0]['description'];
		
		fwrite($handle, 'result=' . var_export($result, true) . "\n");
		
		
		switch ( $message )
		{
			case ezi18n( 'kernel/content/upload','Permission denied'): $status = '410'; break;
			case ezi18n( 'kernel/content/upload','No HTTP file found, cannot fetch uploaded file.'): $status = '411'; break;
			case ezi18n( 'kernel/content/upload','There was an error trying to instantiate content upload handler.'): $status = '412'; break;
			case ezi18n( 'kernel/content/upload','No matching class identifier found.'): $status = '413'; break;
			case ezi18n( 'kernel/content/upload','Was not able to figure out placement of object.'): $status = '414'; break;
			case ezi18n( 'kernel/content/upload','No matching file attribute found, cannot create content object without this.'): $status = '415'; break;
			case ezi18n( 'kernel/content/upload','A file is required for upload, no file were found.'): $status = '416'; break;
			
			
			default: $status = '404 Not Found';
		}
		
		header("HTTP/1.1 $status");
		header("Status: $status");
	}
		fclose($handle);
	eZExecution::cleanExit();
}




?>