<?php
//
// Description
// -----------
// This function will return a list of categories for the web blog page.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_blog_web_tags($ciniki, $settings, $business_id, $tag_type, $blogtype) {

	$strsql = "SELECT ciniki_blog_post_tags.tag_type, "
		. "ciniki_blog_post_tags.tag_name, "
		. "ciniki_blog_post_tags.permalink, "
		. "COUNT(ciniki_blog_post_tags.post_id) AS num_tags, "
		. "MAX(ciniki_blog_posts.primary_image_id) AS image_id "
		. "FROM ciniki_blog_post_tags, ciniki_blog_posts "
		. "WHERE ciniki_blog_post_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	if( $tag_type > 0 ) {
		$strsql .= "AND ciniki_blog_post_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $tag_type) . "' ";
	}
	$strsql .= "AND ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
		. "AND ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_blog_posts.status = 40 "
		. "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
		. "";
	if( $blogtype == 'memberblog' ) {
		$strsql .= "AND (ciniki_blog_posts.publish_to&0x04) = 0x04 ";
	} else {
		$strsql .= "AND (ciniki_blog_posts.publish_to&0x01) = 0x01 ";
	}
	$strsql .= "GROUP BY ciniki_blog_post_tags.tag_type, ciniki_blog_post_tags.tag_name "
		. "ORDER BY ciniki_blog_post_tags.tag_type, ciniki_blog_post_tags.tag_name, ciniki_blog_posts.primary_image_id ASC, ciniki_blog_posts.date_added DESC "
		. "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
		array('container'=>'types', 'fname'=>'tag_type', 
			'fields'=>array('type'=>'tag_type')),
		array('container'=>'tags', 'fname'=>'permalink', 
			'fields'=>array('name'=>'tag_name', 'permalink', 'num_tags', 'image_id')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['types']) ) {
		return array('stat'=>'ok');
	}
	$types = $rc['types'];

	return array('stat'=>'ok', 'types'=>$types);	
}
?>
