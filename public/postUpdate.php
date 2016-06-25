<?php
//
// Description
// -----------
// This method updates the core details for a blog post.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_blog_postUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'post_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post'), 
        'format'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Format'),
        'title'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Title'),
        'subtitle'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Subtitle'),
        'permalink'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Permalink'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status',
            'validlist'=>array('10', '40', '60')), 
        'publish_to'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Publish To'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'primary_image_caption'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Caption'),
        'excerpt'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Excerpt'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
        'publish_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Publish Date'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        'tags'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Tags'),
        'webcollections'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Web Collections'),
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['business_id'], 'ciniki.blog.postUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    if( isset($args['title']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);

        //
        // Check the permalink does not already exist
        //
        $strsql = "SELECT id "
            . "FROM ciniki_blog_posts "
            . "WHERE permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'post');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['post']) || (isset($rc['rows']) && count($rc['rows']) > 0) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1587', 'msg'=>'You already have a post with that title, you\'ll need to choose another.'));
        }
    }

    //
    // Check if publish_date was changed, setup the year/month
    //
    if( isset($args['publish_date']) ) {
        //
        // Load the business settings
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
    }

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.blog');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the post
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.blog.post', 
        $args['post_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
        return $rc;
    }

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.blog', 'tag', $args['business_id'],
            'ciniki_blog_post_tags', 'ciniki_blog_history',
            'post_id', $args['post_id'], 10, $args['categories']);
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
            'post_id', $args['post_id'], 20, $args['tags']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
            return $rc;
        }
    }

    //
    // If there are subscriptions, update them now
    //
    if( isset($ciniki['business']['modules']['ciniki.subscriptions'])
        && isset($ciniki['business']['modules']['ciniki.mail'])
        && ($ciniki['business']['modules']['ciniki.blog']['flags']&0x7000) > 0  // Blog subscriptions enabled
//      && isset($args['subscriptions_ids']) && is_array($args['subscriptions_ids'])
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectUpdateSubscriptions');
        $rc = ciniki_mail_hooks_objectUpdateSubscriptions($ciniki, $args['business_id'], array(
            'object'=>'ciniki.blog.post', 'object_id'=>$args['post_id']));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
            return $rc;
        }
    }

    //
    // If post was updated ok, Check if any web collections to add
    //
    if( isset($args['webcollections'])
        && isset($ciniki['business']['modules']['ciniki.web']) 
        && ($ciniki['business']['modules']['ciniki.web']['flags']&0x08) == 0x08
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionUpdate');
        $rc = ciniki_web_hooks_webCollectionUpdate($ciniki, $args['business_id'],
            array('object'=>'ciniki.blog.post', 'object_id'=>$args['post_id'], 
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

    return array('stat'=>'ok');
}
?>
