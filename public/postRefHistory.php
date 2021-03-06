<?php
//
// Description
// -----------
// This method will return the history for a field that is part of a relationship.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the history for.
// ref_id:              The ID of the blog post reference to get the history for.
// field:               The field to get the history for.
//
// Returns
// -------
//  <history>
//      <action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//      ...
//  </history>
//  <users>
//      <user id="1" name="users.display_name" />
//      ...
//  </users>
//
function ciniki_blog_postRefHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'ref_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reference'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'checkAccess');
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postRefHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'object_id' ) {
        //
        // Get the reference for the object
        //
        $strsql = "SELECT ciniki_blog_post_refs.id, "
            . "ciniki_blog_post_refs.post_id, "
            . "ciniki_blog_post_refs.object, "
            . "ciniki_blog_post_refs.object_id "
            . "FROM ciniki_blog_post_refs "
            . "WHERE ciniki_blog_post_refs.id = '" . ciniki_core_dbQuote($ciniki, $args['ref_id']) . "' "
            . "AND ciniki_blog_post_refs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'ref');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.40', 'msg'=>'Unable to find reference', 'err'=>$rc['err']));
        }
        if( !isset($rc['ref']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.41', 'msg'=>'Reference does not exist'));
        }
        $ref = $rc['ref'];
        //
        // Get the reference
        //
        if( $ref['object'] == 'ciniki.recipes.recipe' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFkId');
            return ciniki_core_dbGetModuleHistoryFkId($ciniki, 'ciniki.blog', 'ciniki_blog_history', 
                $args['tnid'], 'ciniki_blog_post_refs', 
                $args['ref_id'], $args['field'], 'ciniki_recipes', 'id', 'ciniki_recipes.name');
        } elseif( $ref['object'] == 'ciniki.products.product' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFkId');
            return ciniki_core_dbGetModuleHistoryFkId($ciniki, 'ciniki.blog', 'ciniki_blog_history', 
                $args['tnid'], 'ciniki_blog_post_refs', 
                $args['ref_id'], $args['field'], 'ciniki_products', 'id', 'ciniki_products.name');
        }
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.blog', 'ciniki_blog_history', 
        $args['tnid'], 'ciniki_blog_post_refs', $args['ref_id'], $args['field']);
}
?>
