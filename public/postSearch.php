<?php
//
// Description
// ===========
// This method searches the blog posts for string.  It will search the name
// and excerpt by default and only search the content if specified.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_blog_postSearch(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'content_search'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search Content'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
        'blogtype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Blog Type'), 
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['business_id'], 'ciniki.blog.postSearch'); 
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
        . "AND (title LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR title LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR excerpt LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR excerpt LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "";
    if( isset($args['content_search']) && $args['content_search'] == 'yes' ) {
        $strsql .= "OR content LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR content LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "";
    }
    $strsql .= ") "
        . "";
    if( isset($args['status']) && $args['status'] != '' ) {
        $strsql .= "AND status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    if( isset($args['blogtype']) && $args['blogtype'] != '' ) {
        switch($args['blogtype']) {
            case 'blog': $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 "; break;
            case 'memberblog': $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 "; break;
        }
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
