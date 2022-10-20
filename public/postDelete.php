<?php
//
// Description
// -----------
// This method removes a blog post from the tenant.
//
// Returns
// -------
//
function ciniki_blog_postDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'post_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post'),
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postDelete'); 
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
    // get the active modules for the tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'getActiveModules');
    $rc = ciniki_tenants_getActiveModules($ciniki, $args['tnid']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Get the uuid of the blog to be deleted
    //
    $strsql = "SELECT uuid "
        . "FROM ciniki_blog_posts "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'post');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['post']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.9', 'msg'=>'Unable to find existing post'));
    }
    $uuid = $rc['post']['uuid'];

    //  
    // Turn off autocommit
    //  
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.blog');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Delete any references
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_blog_post_refs "
        . "WHERE post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'ref');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $refs = $rc['rows'];
        foreach($refs as $ref) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.blog.postref',
                $ref['id'], $ref['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
                return $rc;
            }
        }
    }
    
    //
    // Delete any links
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_blog_post_links "
        . "WHERE post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'link');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $links = $rc['rows'];
        foreach($links as $link) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.blog.postlink',
                $link['id'], $link['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
                return $rc;
            }
        }
    }
    
    //
    // Delete any images
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_blog_post_images "
        . "WHERE post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'image');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $images = $rc['rows'];
        foreach($images as $image) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.blog.postimage',
                $image['id'], $image['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
                return $rc;
            }
        }
    }
    
    //
    // Delete any files
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_blog_post_files "
        . "WHERE post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'file');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $files = $rc['rows'];
        foreach($files as $file) {
            //
            // Remove file from disk
            //
            $storage_filename = $tenant_storage_dir . '/ciniki.blog/files/' . $file['uuid'][0] . '/' . $file['uuid'];
            if( file_exists($storage_filename) ) {
                unlink($storage_filename);
            }

            //
            // Remove the object
            //
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.blog.postfile',
                $file['id'], $file['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
                return $rc;
            }
        }
    }

    //
    // Remove the event from any web collections
    //
    if( isset($ciniki['tenant']['modules']['ciniki.web']) 
        && ($ciniki['tenant']['modules']['ciniki.web']['flags']&0x08) == 0x08
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionDeleteObjRef');
        $rc = ciniki_web_hooks_webCollectionDeleteObjRef($ciniki, $args['tnid'],
            array('object'=>'ciniki.blog.post', 'object_id'=>$args['post_id']));
        if( $rc['stat'] != 'ok' ) { 
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
            return $rc;
        }
    }

    
    //
    // Delete the post
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.blog.post',
        $args['post_id'], $uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.10', 'msg'=>'Unable to delete post, internal error.'));
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.blog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'blog');

    $ciniki['syncqueue'][] = array('push'=>'ciniki.blog.post', 
        'args'=>array('delete_uuid'=>$uuid, 'delete_id'=>$args['post_id']));

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.blog.post', 'object_id'=>$args['post_id']));

    return array('stat'=>'ok');
}
?>
