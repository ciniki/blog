<?php
//
// Description
// ===========
// This method will add a new file to a blog post.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the file to.
// post_id:             The ID of the blog post the file is attached to.
// name:                The name of the file.
// description:         (optional) The extended description of the file, can be much longer than the name.
// 
// Returns
// -------
//
function ciniki_blog_postFileAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'post_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
        'publish_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'date', 'name'=>'Publish Date'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'checkAccess');
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postFileAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_blog_post_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.11', 'msg'=>'You already have a file with this name, please choose another name'));
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
    // Check to see if an file was uploaded
    //
    if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.12', 'msg'=>'Upload failed, file too large.'));
    }
    // FIXME: Add other checkes for $_FILES['uploadfile']['error']

    //
    // Make sure a file was submitted
    //
    if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.13', 'msg'=>'No file specified.'));
    }

    $args['org_filename'] = $_FILES['uploadfile']['name'];
    $args['extension'] = preg_replace('/^.*\.([a-zA-Z]+)$/', '$1', $args['org_filename']);

    //
    // Check the extension is a PDF, currently only accept PDF files
    //
    if( $args['extension'] != 'pdf' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.14', 'msg'=>'The file must be a PDF file.'));
    }

    //
    // Get a new UUID
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.blog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Move file to storage
    //
    $storage_filename = $tenant_storage_dir . '/ciniki.blog/files/' . $args['uuid'][0] . '/' . $args['uuid'];
    if( !is_dir(dirname($storage_filename)) ) {
        if( !mkdir(dirname($storage_filename), 0700, true) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.96', 'msg'=>'Unable to add file'));
        }
    }

    if( !rename($_FILES['uploadfile']['tmp_name'], $storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.97', 'msg'=>'Unable to add file'));
    }

    //
    // Add the file to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    return ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.blog.postfile', $args, 0x07);
}
?>
