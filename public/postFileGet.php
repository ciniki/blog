<?php
//
// Description
// -----------
// This method returns the details about a file for a blog post.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to file belongs to.
// file_id:             The ID of the file to get.
//
// Returns
// -------
//
function ciniki_blog_postFileGet($ciniki) {
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postFileGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_blog_post_files.id, "
        . "ciniki_blog_post_files.name, "
        . "ciniki_blog_post_files.permalink, "
        . "ciniki_blog_post_files.description "
        . "FROM ciniki_blog_post_files "
        . "WHERE ciniki_blog_post_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_blog_post_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
        . "";

    //
    // Check if we need to include thumbnail images
    //
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.18', 'msg'=>'Unable to find file'));
    }
    
    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
