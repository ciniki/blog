<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_blog_postImageAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'post_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post'), 
        'name'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Title'), 
        'permalink'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Permalink'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'),
        'image_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Image'),
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postImageAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    } 

    //
    // Get a UUID for use in permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.blog');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.24', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Determine the permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        if( isset($args['name']) && $args['name'] != '' ) {
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        } else {
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['uuid']);
        }
    }

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_blog_post_images "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'image');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.25', 'msg'=>'You already have an image with this name, please choose another name'));
    }

    //
    // Check if sequence should be next
    //
    if( !isset($args['sequence']) || $args['sequence'] == '' ) {
        $strsql = "SELECT MAX(sequence) AS seq "
            . "FROM ciniki_blog_post_images "
            . "WHERE post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'max');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['max']['seq']) ) {
            $args['sequence'] = $rc['max']['seq'] + 1;
        }
    }

    if( $args['post_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.26', 'msg'=>'No blog post specified'));
    }
   
    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.blog');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the post image to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.blog.postimage', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
        return $rc;
    }
    $image_id = $rc['id'];

    //
    // Update the sequences
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
    $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.blog.postimage', 'post_id', $args['post_id'], $args['sequence'], -1);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
        return $rc;
    }

    //
    // Commit the transaction
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

    return array('stat'=>'ok', 'id'=>$image_id);
}
?>
