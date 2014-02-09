<?php
//
// Description
// -----------
// This method will add a new post to a business blog.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_blog_postAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'title'=>array('required'=>'yes', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Title'),
		'permalink'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Permalink'),
        'status'=>array('required'=>'no', 'default'=>'10', 'blank'=>'yes', 'name'=>'Status'), 
        'excerpt'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Excerpt'),
        'content'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Content'),
        'publish_date'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Publish Date'),
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['business_id'], 'ciniki.blog.postAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	if( !isset($args['permalink']) || $args['permalink'] == '' ) {
		$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-\/]/', '', strtolower($args['title'])));
	}

	//
	// Check the permalink does not already exist
	//
	$strsql = "SELECT id "
		. "FROM ciniki_blog_posts "
		. "WHERE permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'post');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['post']) || (isset($rc['rows']) && count($rc['rows']) > 0) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1559', 'msg'=>'You already have a post with that title, please choose another'));
	}

	//
	// Add the post
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.blog.post', $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$post_id = $rc['id'];

	return array('stat'=>'ok', 'id'=>$post_id);
}
?>
