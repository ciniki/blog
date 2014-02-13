<?php
//
// Description
// -----------
// This method will update an existing blog post reference.  
//
// The object cannot be changed, only the object_id.  If it should be a different object,
// then the reference needs to be deleted and added new.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the reference belongs to.
// ref_id:				The ID of the blog post reference to update.
// object_id:			The ID of the object.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_blog_postRefUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'ref_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post Reference'), 
        'object_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Object'), 
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['business_id'], 'ciniki.blog.postRefUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the existing post_id and object_id to make sure we're not adding a duplicate
	//
	$strsql = "SELECT id, post_id, object, object_id "
		. "FROM ciniki_blog_post_refs "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['ref_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'ref');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['ref']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1584', 'msg'=>'Unable to find existing blog post reference'));
	}
	$ref = $rc['ref'];

	//
	// Check for blank or undefined object_id
	//
	if( isset($args['object_id']) && ($args['object_id'] == '' || $args['object_id'] == '0') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1585', 'msg'=>'Please specify a object reference.'));
	}

	//
	// Check if reference already exists
	//
	if( isset($args['object_id']) ) {
		$post_id = $ref['post_id'];
		$object = $ref['object'];
		$strsql = "SELECT id "
			. "FROM ciniki_blog_post_refs "
			. "WHERE post_id = '" . ciniki_core_dbQuote($ciniki, $post_id) . "' "
			. "AND object = '" . ciniki_core_dbQuote($ciniki, $object) . "' "
			. "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'ref');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1586', 'msg'=>'Reference already exists for this post'));
		}
	}

	//
	// Update the existing post reference
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.blog.postref',
		$args['ref_id'], $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
