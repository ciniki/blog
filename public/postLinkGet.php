<?php
//
// Description
// -----------
// This method returns the information about a link attached to a blog post.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the link is attached to.
// link_id:             The ID of the link to get.
//
// Returns
// -------
//
function ciniki_blog_postLinkGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'link_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Link'),
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postLinkGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_blog_post_links.id, "
        . "ciniki_blog_post_links.name, "
        . "ciniki_blog_post_links.url, "
        . "ciniki_blog_post_links.description "
        . "FROM ciniki_blog_post_links "
        . "WHERE ciniki_blog_post_links.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_blog_post_links.id = '" . ciniki_core_dbQuote($ciniki, $args['link_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'links', 'fname'=>'id', 'name'=>'link',
            'fields'=>array('id', 'name', 'url', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['links']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.blog.33', 'msg'=>'Unable to find link'));
    }
    $link = $rc['links'][0]['link'];
    
    return array('stat'=>'ok', 'link'=>$link);
}
?>
