<?php
//
// Description
// ===========
// This method will return the existing categories and tags for blog posts.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the item from.
// 
// Returns
// -------
//
function ciniki_blog_postTags($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postTags'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];


    $rsp = array('stat'=>'ok');

    //
    // Get the list of categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
    $rc = ciniki_core_tagsList($ciniki, 'ciniki.blog', $args['tnid'], 'ciniki_blog_post_tags', 10);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        $rsp['categories'] = $rc['tags'];
    } else {
        $rsp['categories'] = array();
    }

    //
    // Get the list of tags
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
    $rc = ciniki_core_tagsList($ciniki, 'ciniki.blog', $args['tnid'], 'ciniki_blog_post_tags', 20);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        $rsp['tags'] = $rc['tags'];
    } else {
        $rsp['tags'] = array();
    }

    //
    // Get the list of web collections, and which ones this post is attached to
    //
    if( isset($ciniki['tenant']['modules']['ciniki.web']) 
        && ($ciniki['tenant']['modules']['ciniki.web']['flags']&0x08) == 0x08
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionList');
        $rc = ciniki_web_hooks_webCollectionList($ciniki, $args['tnid'],
            array('object'=>'ciniki.blog.post', 'object_id'=>0));
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['collections']) ) {
            $rsp['webcollections'] = $rc['collections'];
        } else {
            $rsp['webcollections'] = array();
        }
    }

    return $rsp;
}
?>
