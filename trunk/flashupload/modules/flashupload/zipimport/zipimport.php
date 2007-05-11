<?php
//
// Definition of eZOoimport class
//
// Created on: <17-Jan-2005 09:11:41 bf>
//
// Copyright (C) 1999-2005 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE included in
// the packaging of this file.
//
// Licencees holding a valid "eZ publish professional licence" version 2
// may use this file in accordance with the "eZ publish professional licence"
// version 2 Agreement provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" version 2 is available at
// http://ez.no/ez_publish/licences/professional/ and in the file
// PROFESSIONAL_LICENCE included in the packaging of this file.
// For pricing of this licence please contact us via e-mail to licence@ez.no.
// Further contact information is available at http://ez.no/company/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.

include_once( 'lib/ezxml/classes/ezxml.php' );
include_once( 'lib/ezlocale/classes/ezdatetime.php' );

include_once( "lib/ezutils/classes/ezmimetype.php" );
include_once( 'lib/ezutils/classes/ezoperationhandler.php' );

class ZipImport
{

    var $ImportDir = "var/cache/zipimport/import/";
    /*!
     Constructor
    */
    function ZipImport()
    {
    }

    /*!
      Imports an OpenOffice.org document from the given file.
    */
    function import( $file, $placeNodeID, $originalFileName )
    {
     
        $importResult = array();
        include_once( "lib/ezfile/classes/ezdir.php" );
        $unzipResult = "";
        eZDir::mkdir( $this->ImportDir );


        // Check if zlib extension is loaded, if it's loaded use bundled ZIP library,
        // if not rely on the unzip commandline version.
        if ( !function_exists( 'gzopen' ) )
        {
            exec( "unzip -o $file -d " . $this->ImportDir, $unzipResult );
        }
        else
        {
            require_once('extension/zipimport/lib/pclzip.lib.php');
            $archive = new PclZip( $file );
            $archive->extract( PCLZIP_OPT_PATH, $this->ImportDir );
        }
        
        $parentNode =& eZContentObjectTreeNode::fetch( $placeNodeID );
        $this->importDir($this->ImportDir,$parentNode,false);
    }
	      
    function importDir($dirName,$parentNode,$current=true)
    {
        
        if ( $handle = opendir( $dirName ))
    	{

        	$pathParts = explode( '/',$dirName );
        	$file = $pathParts[count( $pathParts ) -2 ];
        	if ($current) {
	        	$folder =& $this->addFolder( $dirName, $file, $parentNode );
	        } else {
	        	$folder =& $parentNode;
	        }

	        /* This is the correct way to loop over the directory. */
	        while (false !== ($file = readdir($handle)))
	        {
	            if ( $file == '.' || $file == '..' )
	                continue;
	
	            if (  filetype( $dirName . '/' . $file ) == 'dir' )
	            {
	                $this->importDir( $dirName . '/' . $file . '/' , $folder );
	            }
	            else
	            {
	                $this->addImage( $dirName . '/' . $file, $file, $folder );
	            }
	
	        }
	        closedir($handle);
    	}

        // Clean up
        eZDir::recursiveDelete( $this->ImportDir );
        return $importResult;
    }
    
	function &addFolder( $folderFile, $folderName, $parent )
	{
	    $db =& eZDB::instance();
		$db->setIsSQLOutputEnabled( true );
		//fetch folder class
		$class =& eZContentClass::fetch( 1 );
	
	    $folderName = $folderName;
	    $folderDescription = $folderName;
	    $folderCreatedTime = filemtime( $folderFile );
	    $folderFileName = $folderName;
	    $folderOriginalFileName = $folderName;
	    $folderCaption = $folderName;
	    $remoteID = "folder_" . $folderName;
		//$userID = eZUser::currentUserID();
	    $userID = 14;
	
	    $contentObject =& $class->instantiate( $userID, 1 );
	    $contentObject->setAttribute('remote_id', $remoteID );
	    $contentObject->setAttribute( 'name', $folderName );
	
	    $nodeAssignment =& eZNodeAssignment::create( array(
	                                                     'contentobject_id' => $contentObject->attribute( 'id' ),
	                                                     'contentobject_version' => $contentObject->attribute( 'current_version' ),
	                                                     'parent_node' => $parent->attribute( 'node_id' ),
	                                                     'sort_field' => 2,
	                                                     'sort_order' => 0,
	                                                     'is_main' => 1
	                                                     )
	                                                 );
	    $nodeAssignment->store();
	
	    $version =& $contentObject->version( 1 );
	    $version->setAttribute( 'modified', $folderCreatedTime );
	    $version->setAttribute( 'created', $folderCreatedTime );
	    $version->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
	    $version->store();
	
	    $contentObjectID = $contentObject->attribute( 'id' );
	    $contentObjectAttributes =& $version->contentObjectAttributes();
	
	    $contentObjectAttributes[0]->setAttribute( 'data_text', $folderName );
	    $contentObjectAttributes[0]->store();
	    $contentObjectAttributes[1]->setAttribute( 'data_text', $folderDescription );
	    $contentObjectAttributes[1]->store();
	
	
	    include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
	    $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID,
	                                                                                 'version' => 1 ) );
	    $contentObject->setAttribute('modified', $folderCreatedTime );
	    $contentObject->setAttribute('published', $folderCreatedTime );
	    $contentObject->store();
	    $contentObject =& eZContentObject::fetch( $contentObject->attribute( 'id' ) );
	
	    echo $contentObject->attribute( 'main_node_id' );
	    return eZContentObjectTreeNode::fetch( $contentObject->attribute( 'main_node_id' ) );
	}
	
function addImage( $imageFile, $imagename, $parent )
{
    $ini =& eZINI::instance( 'multiplefileupload.ini' );
    $imageContentClassID =  (int) $ini->variable( 'GeneralSettings', 'ImageContentClassID' );
    $nameAttributeNumber =  (int) $ini->variable( 'GeneralSettings', 'NameAttributeNumber' );
    $imageAttributeNumber = (int) $ini->variable( 'GeneralSettings', 'ImageAttributeNumber' );

    if ( $imageContentClassID == 0 )  $imageContentClassID = 5;
    if ( $nameAttributeNumber == 0 )  $nameAttributeNumber = 1;
    if ( $imageAttributeNumber == 0 ) $imageAttributeNumber = 3;

    $nameAttributeIndex = $nameAttributeNumber - 1;
    $imageAttributeIndex = $imageAttributeNumber - 1;

    $mimeObj = new eZMimeType();
    // $mime = $mimeObj->mimeTypeFor( false, $imageFile );
    $mime = $mimeObj->mimeTypeFor( false, $imagename );
    $mimeArray = explode( '/', $mime );

    if ( $mimeArray[0] != 'image' )
	{
		$this->addFile( $imageFile, $imagename, $parent );
        return;
	}

    $db =& eZDB::instance();
    $db->setIsSQLOutputEnabled( false );
    $class =& eZContentClass::fetch( $imageContentClassID );

    unset( $contentObject );

    // try to extract the priority from the filename
    preg_match( "/^(\d+)[\s-]*([^\s-].+)\..+$/i", $imagename, $matches );
    $priority = (int) $matches[1];
    $imageName = $matches[2];

    // if the extraction of the priority fails, get the filename (w/o extension) as imagename)
    if ( $imageName == "" )
    {
        preg_match( "/^(.+?)\..+?$/i", $imagename, $matches );
        $imageName = $matches[1];
    }

    $imageDescription = $imageName;
    $imageCreatedTime = filemtime( $imageFile );
    $imageFileName = $imagename;
    $imageOriginalFileName = $imagename;
    $imageCaption = $imageName;

    // set remoteID
    $remoteID = "image_" . $imageName;

    $userID = eZUser::currentUserID();

    if ( $userID != null )
    {
        $object = $parent->object();

        // Create object by user id in the section of the parent object
        $contentObject =& $class->instantiate( $userID, $object->attribute( 'section_id' ) );
        $contentObject->setAttribute('remote_id', $remoteID );
        $contentObject->setAttribute( 'name', $imageName );

        $nodeAssignment =& eZNodeAssignment::create( array(
                                                         'contentobject_id' => $contentObject->attribute( 'id' ),
                                                         'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                         'parent_node' => $parent->attribute( 'node_id' ),
                                                         'sort_field' => 2,
                                                         'sort_order' => 0,
                                                         'is_main' => 1
                                                         )
                                                     );
        $nodeAssignment->store();

        $version =& $contentObject->version( 1 );

        $contentObjectID = $contentObject->attribute( 'id' );
        $contentObjectAttributes =& $version->contentObjectAttributes();

        $contentObjectAttributes[$nameAttributeIndex]->setAttribute( 'data_text', $imageName );
        $contentObjectAttributes[$nameAttributeIndex]->store();

        $contentObjectAttribute =& $contentObjectAttributes[$imageAttributeIndex];

        $imagefile = $this->saveImage( $imageFile, $imagename, $imageCaption, $contentObjectAttribute );
        $contentObjectAttributes[$imageAttributeIndex]->store();

        $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID,
                                                                                     'version' => 1 ) );
        // set the priority of the node
        $assignedNodes = $contentObject->assignedNodes();
        $assignedNodes[0]->setAttribute( 'priority', $priority );
        $assignedNodes[0]->store();

        // unlink the eZ 3.2 "original" image
        unlink( $imagefile );
    }
}

function saveImage( $sourceImage, $originalImageFileName, $caption, &$contentObjectAttribute )
{
    include_once( "lib/ezutils/classes/ezdir.php" );
    $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
    $version = $contentObjectAttribute->attribute( "version" );

    include_once( "kernel/classes/datatypes/ezimage/ezimage.php" );
    $image =& eZImage::create( $contentObjectAttributeID , $version );

    $image->setAttribute( "contentobject_attribute_id", $contentObjectAttributeID );
    $image->setAttribute( "version", $version );


    $sys =& eZSys::instance();
    $storage_dir = $sys->storageDirectory();
    $nameArray = explode( '.', $originalImageFileName );
    $ext = $nameArray[ count($nameArray ) - 1 ];
    $uniqueName = tempnam( $storage_dir . "/original/image/", "imp");
    unlink( $uniqueName );
    $uniqueName .= '.'.$ext;
    $separator = ( substr( php_uname(), 0, 7 ) == "Windows" ) ? '\\' : '/';
    $uniqueNameArray = explode( $separator, $uniqueName );
    $uniqueNameFile = $uniqueNameArray[ count( $uniqueNameArray ) - 1 ];
    $image->setAttribute( "filename", $uniqueNameFile );
    $image->setAttribute( "original_filename", $originalImageFileName );

    $mimeObj = new eZMimeType();
    $mime = $mimeObj->mimeTypeFor( false, $originalImageFileName );
    $image->setAttribute( "mime_type", $mime );
    $image->setAttribute( "alternative_text", $caption );
    $image->store();

    $sys =& eZSys::instance();
    $storage_dir = $sys->storageDirectory();

    $ori_dir = $storage_dir . '/' . "original/image";
    if ( !file_exists( $ori_dir ) )
    {
        eZDir::mkdir( $ori_dir, 0777, true);
    }

    $source_file = $sourceImage;
    $target_file = $storage_dir . "/original/image/" . $uniqueNameFile;
    copy($source_file, $target_file );

    return $target_file;
}
	
	function addFile( $file, $filename, $parent )
	{
	    $db =& eZDB::instance();
		$db->setIsSQLOutputEnabled( false );
		$class =& eZContentClass::fetch( 12 );
	
	    unset( $contentObject );
	    $nameArray = explode( '.', $filename );
	    $fileName = $nameArray[0];
	    $fileDescription = $fileName;
	    $fileCreatedTime = filemtime( $file );
	    $originalFileName = $filename;
	    $fileCaption = $fileName;
	
	    // set remoteID
	    $remoteID = "file_" . $fileName;
	
	    $userID = 14;

        // Create object by user id in section 1
        $contentObject =& $class->instantiate( $userID, 1 );
        $contentObject->setAttribute('remote_id', $remoteID );
        $contentObject->setAttribute( 'name', $fileName );

        $nodeAssignment =& eZNodeAssignment::create( array(
                                                         'contentobject_id' => $contentObject->attribute( 'id' ),
                                                         'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                         'parent_node' => $parent->attribute( 'node_id' ),
                                                         'sort_field' => 2,
                                                         'sort_order' => 0,
                                                         'is_main' => 1
                                                         )
                                                     );
        $nodeAssignment->store();

        $version =& $contentObject->version( 1 );
        $version->setAttribute( 'modified', $fileCreatedTime );
        $version->setAttribute( 'created', $fileCreatedTime );
        $version->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
        $version->store();

        $contentObjectID = $contentObject->attribute( 'id' );
        $contentObjectAttributes =& $version->contentObjectAttributes();

        $contentObjectAttributes[0]->setAttribute( 'data_text', $fileName );
        $contentObjectAttributes[0]->store();
        $contentObjectAttributes[1]->setAttribute( 'data_text', $fileDescription );
        $contentObjectAttributes[1]->store();

        $contentObjectAttribute =& $contentObjectAttributes[2];

        $this->saveFile( $file, $filename, $fileCaption, $contentObjectAttribute );
        $contentObjectAttributes[2]->store();

        include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
        $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID,
                                                                                 'version' => 1 ) );
        $contentObject->setAttribute('modified', $fileCreatedTime );
        $contentObject->setAttribute('published', $fileCreatedTime );
        $contentObject->store();
	}
	
	function saveFile( $sourceFile, $originalFileName, $caption, &$contentObjectAttribute )
	{

	 	include_once( "lib/ezutils/classes/ezdir.php" );   
	 	
	    $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
	    $version = $contentObjectAttribute->attribute( "version" );
	
	    include_once( "kernel/classes/datatypes/ezbinaryfile/ezbinaryfile.php" );
	    include_once( "kernel/classes/datatypes/ezbinaryfile/ezbinaryfiletype.php" );
	    $file =& eZBinaryFile::create( $contentObjectAttributeID , $version );
	    
	    $file->setAttribute( "contentobject_attribute_id", $contentObjectAttributeID );
	    $file->setAttribute( "version", $version );
	    
	    	
	    $mimeObj = new eZMimeType();
	    $mime = $mimeObj->mimeTypeFor( false, $originalFileName );
	    eZDebug::writeError( "$mime" );
	    
	    $aMime = explode("/",$mime);
	    $main_mime = $aMime[0];
	    eZDebug::writeError( "$main_mime" );
	    $file->setAttribute( "mime_type", $mime );
	
	
	    $sys =& eZSys::instance();
	    $storage_dir = $sys->storageDirectory();
	    $nameArray = explode( '.', $originalFileName );
	    $ext = $nameArray[ count($nameArray ) - 1 ];
	    $uniqueName = tempnam( $storage_dir . "/original/{$main_mime}/", "imp") . '.' . $ext;
	    $uniqueNameArray = explode( '/', $uniqueName );
	    $uniqueNameFile = $uniqueNameArray[ count( $uniqueNameArray ) - 1 ];
	    $file->setAttribute( "filename", $uniqueNameFile);
	    $file->setAttribute( "original_filename", $originalFileName);
   	    $file->store(); 

	    $sys =& eZSys::instance();
	    $storage_dir = $sys->storageDirectory();
	
	    $ori_dir = $storage_dir . '/' . "original/{$main_mime}";
	    if ( !file_exists( $ori_dir ) )
	    {
	        eZDir::mkdir( $ori_dir, 0777, true);
	    }
	    
	    $source_file = $sourceFile;
	    $target_file = $storage_dir . "/original/{$main_mime}/" . $uniqueNameFile;
	    copy($source_file, $target_file );
	}
	    
}
?>
