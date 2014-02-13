<?php
//
// Description
// -----------
// This function will return a list of posts organized by date
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get events for.
//
// args:			The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_blog_web_posts($ciniki, $settings, $business_id, $args) {

	//
	// Load the business settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	//
	// Build the query string to get the posts
	//
	$strsql = "SELECT ciniki_blog_posts.id, "
		. "ciniki_blog_posts.publish_date AS name, "
		. "ciniki_blog_posts.publish_date AS publish_time, "
		. "ciniki_blog_posts.title, "
		. "ciniki_blog_posts.permalink, "
		. "ciniki_blog_posts.primary_image_id, "
		. "ciniki_blog_posts.excerpt, "
		. "'yes' AS is_details "
		. "";

	if( isset($args['category']) && $args['category'] != '' ) {
		$strsql .= ", ciniki_blog_post_tags.tag_name "
			. "FROM ciniki_blog_post_tags "
			. "LEFT JOIN ciniki_blog_posts ON (ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
				. "AND ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_blog_posts.status = 40 "
				. "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
				. ") "
			. "WHERE ciniki_blog_post_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_blog_post_tags.tag_type = 10 "
			. "AND ciniki_blog_post_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
			. "";
	} elseif( isset($args['tag']) && $args['tag'] != '' ) {
		$strsql .= ", ciniki_blog_post_tags.tag_name "
			. "FROM ciniki_blog_post_tags "
			. "LEFT JOIN ciniki_blog_posts ON (ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
				. "AND ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_blog_posts.status = 40 "
				. "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
				. ") "
			. "WHERE ciniki_blog_post_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_blog_post_tags.tag_type = 20 "
			. "AND ciniki_blog_post_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['tag']) . "' "
			. "";
	} elseif( isset($args['year']) && $args['year'] != '' ) {
		if( isset($args['month']) && $args['month'] != '' ) {
			// Build the start and end datetimes
			$tz = new DateTimeZone($intl_timezone);
			$start_date = new DateTime($args['year'] . '-' . $args['month'] . '-01 00.00.00', $tz);
			$end_date = clone $start_date;
			// Find the end of the month
			$end_date->add(new DateInterval('P1M'));
		} else {
			$tz = new DateTimeZone($intl_timezone);
			$start_date = new DateTime($args['year'] . '-01-01 00.00.00', $tz);
			$end_date = clone $start_date;
			// Find the end of the month
			$end_date->add(new DateInterval('P1Y'));
		}
		$start_date->setTimezone(new DateTimeZone('UTC'));
		$end_date->setTimezone(new DateTimeZone('UTC'));

		$strsql .= ", 'unknown' AS tag_name "
			. "FROM ciniki_blog_posts "
			. "WHERE ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_blog_posts.status = 40 "
			. "AND ciniki_blog_posts.publish_date >= '" . $start_date->format('Y-m-d H:i:s') . "' "
			. "AND ciniki_blog_posts.publish_date < '" . $end_date->format('Y-m-d H:i:s') . "' "
			. "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
			. "";
	} else {
		$strsql .= ", 'unknown' AS tag_name "
			. "FROM ciniki_blog_posts "
			. "WHERE ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_blog_posts.status = 40 "
			. "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
			. "";
	}

	$strsql .= "ORDER BY ciniki_blog_posts.publish_date DESC ";
	if( isset($args['offset']) && $args['offset'] > 0 
		&& isset($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . $args['offset'] . ', ' . $args['limit'];
	} elseif( isset($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . $args['limit'];
	}

	//
	// Get the list of posts, sorted by publish_date for use in the web CI List Categories
	//
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
		array('container'=>'posts', 'fname'=>'name', 
			'fields'=>array('name', 'tag_name'),
			'utctotz'=>array('name'=>array('timezone'=>$intl_timezone, 'format'=>'M j, Y')),
			),
		array('container'=>'list', 'fname'=>'id',
			'fields'=>array('id', 'title', 'permalink', 'image_id'=>'primary_image_id', 
				'description'=>'excerpt', 'is_details')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['posts']) ) {
		$posts = $rc['posts'];

	} else {
		$posts = array();
	}

	return array('stat'=>'ok', 'posts'=>$posts);
}
?>
