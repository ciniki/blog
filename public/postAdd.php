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
		'format'=>array('required'=>'no', 'default'=>'10', 'blank'=>'no', 'name'=>'Format'),
		'title'=>array('required'=>'yes', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Title'),
		'subtitle'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Subtitle'),
		'permalink'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Permalink'),
        'status'=>array('required'=>'no', 'default'=>'10', 'blank'=>'yes', 'name'=>'Status',
			'validlist'=>array('10', '40', '60')),
        'publish_to'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Publish To'),
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'),
        'primary_image_caption'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Caption'),
        'excerpt'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Excerpt'),
        'content'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Content'),
        'publish_date'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Publish Date'),
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
		'tags'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Tags'),
		'webcollections'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Web Collections'),
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

	$args['user_id'] = $ciniki['session']['user']['id'];

	if( !isset($args['permalink']) || $args['permalink'] == '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);
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
	// Check if publish_date was specified, and convert to local time and get year and month
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	//
	// Setup the year and month
	//
	$date = new DateTime($args['publish_date'], new DateTimeZone('UTC'));
	$date->setTimezone(new DateTimeZone($intl_timezone));
	$args['publish_year'] = $date->format('Y');
	$args['publish_month'] = $date->format('m');

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.blog');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the post
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.blog.post', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
		return $rc;
	}
	$post_id = $rc['id'];

	//
	// Update the categories
	//
	if( isset($args['categories']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.blog', 'tag', $args['business_id'],
			'ciniki_blog_post_tags', 'ciniki_blog_history',
			'post_id', $post_id, 10, $args['categories']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
			return $rc;
		}
	}

	//
	// Update the tags
	//
	if( isset($args['tags']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.blog', 'tag', $args['business_id'],
			'ciniki_blog_post_tags', 'ciniki_blog_history',
			'post_id', $post_id, 20, $args['tags']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
			return $rc;
		}
	}

	//
	// If there are subscriptions, add them now
	//
	if( isset($ciniki['business']['modules']['ciniki.subscriptions'])
		&& isset($ciniki['business']['modules']['ciniki.mail'])
		&& ($ciniki['business']['modules']['ciniki.blog']['flags']&0x7000) > 0 	// Blog subscriptions enabled
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectUpdateSubscriptions');
		$rc = ciniki_mail_hooks_objectUpdateSubscriptions($ciniki, $args['business_id'], array(
			'object'=>'ciniki.blog.post', 'object_id'=>$post_id));
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
			return $rc;
		}
/*		//
		// Get the active list of subscriptions
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'hooks', 'subscriptionList');
		$rc = ciniki_subscriptions_hooks_subscriptionList($ciniki, $args['business_id'], array());
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
			return $rc;
		}
		
		//
		// Add the subscriptions
		//
		if( isset($rc['subscriptions']) ) {
			$subscriptions = $rc['subscriptions'];
			foreach($rc['subscriptions'] as $subscription_id => $subscription) {
				if( isset($ciniki['request']['args']['subscription-' . $subscription_id]) 
					&& $ciniki['request']['args']['subscription-' . $subscription_id] == 'yes' 
					) {
					$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.blog.post_subscription', array(
						'post_id'=>$post_id,
						'subscription_id'=>$subscription_id,
						'status'=>'10'), 0x04);
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
						return $rc;
					}
				}
//				if( in_array($subscription_id, $args['subscriptions_ids']) ) {
//					$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.blog.post_subscription', array(
//						'post_id'=>$post_id,
//						'subscription_id'=>$subscription_id,
//						'status'=>'10'), 0x04);
//					if( $rc['stat'] != 'ok' ) {
//						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
//						return $rc;
//					}
//				}
			}
		} */
	}

	//
	// If post was added ok, Check if any web collections to add
	//
	if( isset($args['webcollections'])
		&& isset($ciniki['business']['modules']['ciniki.web']) 
		&& ($ciniki['business']['modules']['ciniki.web']['flags']&0x08) == 0x08
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionUpdate');
		$rc = ciniki_web_hooks_webCollectionUpdate($ciniki, $args['business_id'],
			array('object'=>'ciniki.blog.post', 'object_id'=>$post_id, 
				'collection_ids'=>$args['webcollections']));
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
			return $rc;
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.blog');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'blog');

	return array('stat'=>'ok', 'id'=>$post_id);
}
?>
