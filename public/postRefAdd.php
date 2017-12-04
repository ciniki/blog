<?php
//
// Description
// -----------
// This method will add a ref to a blog post.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the blog post belongs to.
// post_id:             The ID of the post to add the reference to.
// object:              The object of the reference.
// object_id:           The ID of the object reference.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_blog_postRefAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'post_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post'), 
        'object'=>array('required'=>'yes', 'blank'=>'no', 
            'validlist'=>array(
                'ciniki.products.product',
                'ciniki.recipes.recipe',
                ),
            'name'=>'Object'), 
        'object_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object ID'), 
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postRefAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check the referenced object exists
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckExists');
    $rc = ciniki_core_objectCheckExists($ciniki, $args['tnid'], $args['object'], $args['object_id']);
    if( $rc['stat'] == 'noexist' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.36', 'msg'=>'Object does not exist'));
    }
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check to make sure the ref is not already connected to the blog post
    //
    $strsql = "SELECT id "
        . "FROM ciniki_blog_post_refs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
        . "AND object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
        . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.refs', 'ref');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.37', 'msg'=>'Object is already attached to the blog post'));
    }

    //
    // Add the relationship
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    return ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.blog.postref', $args, 0x07);
}
?>
