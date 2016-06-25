<?php
//
// Description
// -----------
// This method returns the details about an object reference that is linked to a blog post.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the reference from.
// ref_id:              The ID of the refernece to get.
// 
// Returns
// -------
//
function ciniki_blog_postRefGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'ref_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object Reference'),
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['business_id'], 'ciniki.blog.postRefGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Build the query to get the details about a reference, including the referenced object_id and name.
    //
    $strsql = "SELECT ciniki_blog_post_refs.id, "
        . "ciniki_blog_post_refs.post_id, "
        . "ciniki_blog_post_refs.object, "
        . "ciniki_blog_post_refs.object_id "
        . "FROM ciniki_blog_post_refs "
        . "WHERE ciniki_blog_post_refs.id = '" . ciniki_core_dbQuote($ciniki, $args['ref_id']) . "' "
        . "AND ciniki_blog_post_refs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'ref');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1582', 'msg'=>'Unable to find reference', 'err'=>$rc['err']));
    }
    if( !isset($rc['ref']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1583', 'msg'=>'Reference does not exist'));
    }
    $ref = $rc['ref'];

    //
    // Load the name of the reference
    //
    $ref['object_name'] = '';
    if( $ref['object'] == 'ciniki.recipes.recipe' ) {
        $strsql = "SELECT name "
            . "FROM ciniki_recipes "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.recipes', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['item']) ) {
            $ref['object_name'] = $rc['item']['name'];
        }
    } elseif( $ref['object'] == 'ciniki.products.product' ) {
        $strsql = "SELECT name "
            . "FROM ciniki_products "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['item']) ) {
            $ref['object_name'] = $rc['item']['name'];
        }
    }

    return array('stat'=>'ok', 'ref'=>$ref);
}
?>
