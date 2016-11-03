<?php
//
// Description
// ===========
// This method will remove an existing link from a blog post.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_blog_postLinkDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'link_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Link'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'checkAccess');
    $rc = ciniki_blog_checkAccess($ciniki, $args['business_id'], 'ciniki.blog.postLinkDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the existing link information
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_blog_post_links "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['link_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.32', 'msg'=>'Post link does not exist'));
    }
    $item = $rc['item'];

    //
    // Delete the object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    return ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.blog.postlink', 
        $args['link_id'], $item['uuid'], 0x07);
}
?>
