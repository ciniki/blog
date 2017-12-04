<?php
//
// Description
// -----------
// This method will add a new post link to a blog post.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_blog_postLinkAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'post_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post'),
        'name'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Name'), 
        'url'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'URL'),
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postLinkAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Check the url does not already exist for this blog post
    //
    $strsql = "SELECT id "
        . "FROM ciniki_blog_post_links "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND url = '" . ciniki_core_dbQuote($ciniki, $args['url']) . "' "
        . "AND post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'link');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['link']) || (isset($rc['rows']) && count($rc['rows']) > 0) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.31', 'msg'=>'You already have a post link with that url, please choose another'));
    }

    //
    // Add the post link
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.blog.postlink', $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $link_id = $rc['id'];

    return array('stat'=>'ok', 'id'=>$link_id);
}
?>
