<?php
//
// Description
// -----------
// This method will update an existing post link to a blog post.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_blog_postLinkUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'link_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Link'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'), 
        'url'=>array('required'=>'no', 'blank'=>'no', 'name'=>'URL'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postLinkUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // If the url is being updated, check the new one does not exist
    //
    if( isset($args['url']) && $args['url'] != '' ) {
        //
        // Get the existing link
        //
        $strsql = "SELECT id, post_id, name, url, description "
            . "FROM ciniki_blog_post_links "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['link_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'link');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['link']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.34', 'msg'=>'The post link does not exist'));
        }
        $link = $rc['link'];

        //
        // Check the url
        //
        $strsql = "SELECT id "
            . "FROM ciniki_blog_post_links "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND url = '" . ciniki_core_dbQuote($ciniki, $args['url']) . "' "
            . "AND post_id = '" . ciniki_core_dbQuote($ciniki, $link['post_id']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $link['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'link');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['link']) || (isset($rc['rows']) && count($rc['rows']) > 0) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.35', 'msg'=>'You already have a post link with that url, please choose another'));
        }
    }

    //
    // Upate the post link
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.blog.postlink', 
        $args['link_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
