<?php
//
// Description
// ===========
// This method will return the file in it's binary form.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the requested file belongs to.
// file_id:         The ID of the file to be downloaded.
//
// Returns
// -------
// Binary file.
//
function ciniki_blog_postFileDownload($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'checkAccess');
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postFileDownload'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Get the uuid for the file
    //
    $strsql = "SELECT id, "
        . "uuid, "
        . "name, "
        . "extension, "
        . "binary_content "
        . "FROM ciniki_blog_post_files "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.16', 'msg'=>'Unable to find file'));
    }
    $filename = $rc['file']['name'] . '.' . $rc['file']['extension'];

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    if( $rc['file']['extension'] == 'pdf' ) {
        header('Content-Type: application/pdf');
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.17', 'msg'=>'Unsupported file type'));
    }

    //
    // Get the storage filename
    //
    $storage_filename = $tenant_storage_dir . '/ciniki.blog/files/' . $rc['file']['uuid'][0] . '/' . $rc['file']['uuid'];
    if( file_exists($storage_filename) ) {  
        $rc['file']['binary_content'] = file_get_contents($storage_filename);    
    }

    // Specify Filename
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Content-Length: ' . strlen($rc['file']['binary_content']));
    header('Cache-Control: max-age=0');

    print $rc['file']['binary_content'];
    exit();
    
    return array('stat'=>'binary');
}
?>
