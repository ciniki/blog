<?php
//
// Description
// ===========
// This method will return the upcoming, drafts, recently published and first publish date.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_blog_postStats(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'drafts'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Drafts'), 
        'upcoming'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Upcoming'), 
        'past'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Past'), 
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['business_id'], 'ciniki.blog.postStats'); 
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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

	$rsp = array('stat'=>'ok');

	//
	// Get the list of draft posts
	//
	if( isset($args['drafts']) && $args['drafts'] == 'yes' ) {
		$strsql = "SELECT id, title, publish_date "
			. "FROM ciniki_blog_posts "
			. "WHERE status = 10 "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY publish_date DESC "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
			array('container'=>'posts', 'fname'=>'id', 'name'=>'post',
				'fields'=>array('id', 'title', 'publish_date'),
				'utctotz'=>array('publish_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
				),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['posts']) ) {
			$rsp['drafts'] = $rc['posts'];
		} else {
			$rsp['drafts'] = array();
		}
	}

	//
	// Get the list of upcoming posts
	//
	if( isset($args['upcoming']) && $args['upcoming'] == 'yes' ) {
		$strsql = "SELECT id, title, publish_date "
			. "FROM ciniki_blog_posts "
			. "WHERE status = 40 "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND publish_date > UTC_TIMESTAMP() "
			. "ORDER BY publish_date ASC "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
			array('container'=>'posts', 'fname'=>'id', 'name'=>'post',
				'fields'=>array('id', 'title', 'publish_date'),
				'utctotz'=>array('publish_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
				),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['posts']) ) {
			$rsp['upcoming'] = $rc['posts'];
		} else {
			$rsp['upcoming'] = array();
		}
	}

	//
	// Get the list of most recent published posts
	//
	if( isset($args['past']) && ($args['past'] == 'yes' || $args['past'] > 0) ) {
		$strsql = "SELECT id, title, publish_date "
			. "FROM ciniki_blog_posts "
			. "WHERE status = 40 "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND publish_date <= UTC_TIMESTAMP() "
			. "ORDER BY publish_date DESC "
			. "";
		if( $args['past'] > 0 ) {
			$strsql .= "LIMIT " . $args['past'] . " ";
		} else {
			$strsql .= "LIMIT 25";
		}
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
			array('container'=>'posts', 'fname'=>'id', 'name'=>'post',
				'fields'=>array('id', 'title', 'publish_date'),
				'utctotz'=>array('publish_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
				),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['posts']) ) {
			$rsp['past'] = $rc['posts'];
		} else {
			$rsp['past'] = array();
		}
	}

	//
	// Get the first year a blog post was published
	//
	if( isset($args['years']) && $args['years'] == 'yes' ) {
		$year_format = "Y";
		$strsql = "SELECT MIN(publish_date) AS min_publish_date, "
			. "MAX(publish_date) AS max_publish_date "
			. "FROM ciniki_blog_posts "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND publish_date <> '0000-00-00 00:00:00' "
			. "AND status = 40 "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'stat');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
			array('container'=>'posts', 'fname'=>'id', 'name'=>'stats',
				'fields'=>array('min_publish_date', 'max_publish_date'),
				'utctotz'=>array(
					'min_publish_date'=>array('timezone'=>$intl_timezone, 'format'=>$year_format),
					'max_publish_date'=>array('timezone'=>$intl_timezone, 'format'=>$year_format)),
				),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['posts'][0]['stats']) ) {
			$rsp['min_year'] = $rc['posts'][0]['post']['min_publish_date']
			$rsp['max_year'] = $rc['posts'][0]['post']['max_publish_date']
		} else {
			$rsp['min_year'] = date('Y');
			$rsp['max_year'] = date('Y');
		}
	}

	return $rsp;
}
?>
