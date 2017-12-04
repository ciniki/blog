<?php
//
// Description
// -----------
// This method will remove an object reference from a blog post.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to remove the reference from.
// ref_id:              The ID of the reference to delete.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_blog_postRefDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'ref_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object Reference'), 
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postRefDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Delete the reference
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    return ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.blog.postref', $args['ref_id'], NULL, 0x07);
}
?>
