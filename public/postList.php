<?php
//
// Description
// ===========
// This method will return a list of posts.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_blog_postList(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'year'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Year'), 
        'month'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Month'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['business_id'], 'ciniki.blog.postList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Load the business settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
	$time_format = ciniki_users_timeFormat($ciniki, 'php');

	//
	// Get the list of posts
	//
	$strsql = "SELECT id, title, "
		. "publish_date, "
		. "publish_date AS publish_time, "
		. "excerpt "
		. "FROM ciniki_blog_posts "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	if( isset($args['year']) && $args['year'] != '' ) {
		//
		// Set the start and end date for the business timezone, then convert to UTC
		//
		$tz = new DateTimeZone($intl_timezone);
		if( isset($args['month']) && $args['month'] != '' && $args['month'] > 0 ) {
			$start_date = new DateTime($args['year'] . '-' . $args['month'] . '-01 00.00.00', $tz);
			$end_date = clone $start_date;
			// Find the end of the month
			$end_date->add(new DateInterval('P1M'));
		} else {
			$start_date = new DateTime($args['year'] . '-01-01 00.00.00', $tz);
			$end_date = clone $start_date;
			// Find the end of the year
			$end_date->add(new DateInterval('P1Y'));
		}
		$start_date->setTimezone(new DateTimeZone('UTC'));
		$end_date->setTimeZone(new DateTimeZone('UTC'));
		//
		// Add to SQL string
		//
		$strsql .= "AND ciniki_blog_posts.publish_date >= '" . $start_date->format('Y-m-d H:i:s') . "' ";
		$strsql .= "AND ciniki_blog_posts.publish_date < '" . $end_date->format('Y-m-d H:i:s') . "' ";
	}
	if( isset($args['status']) && $args['status'] != '' ) {
		$strsql .= "AND status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
	}
	$strsql .= "ORDER BY publish_date DESC "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
		array('container'=>'posts', 'fname'=>'id', 'name'=>'post',
			'fields'=>array('id', 'title', 'publish_date', 'publish_time', 'excerpt'),
			'utctotz'=>array(
				'publish_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'publish_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
			)),
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