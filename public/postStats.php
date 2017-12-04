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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'drafts'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Drafts'), 
        'upcoming'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Upcoming'), 
        'past'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Past'), 
        'years'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Years'), 
        'blogtype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Blog Type'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'checkAccess');
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postStats'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
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

    $rsp = array('stat'=>'ok');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    //
    // Get the list of draft posts
    //
    if( isset($args['drafts']) && $args['drafts'] == 'yes' ) {
        $strsql = "SELECT id, title, "
            . "publish_date, "
            . "publish_date AS publish_time, "
            . "excerpt "
            . "FROM ciniki_blog_posts "
            . "WHERE status = 10 "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        if( isset($args['blogtype']) && $args['blogtype'] != '' ) {
            switch($args['blogtype']) {
                case 'blog': $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 "; break;
                case 'memberblog': $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 "; break;
            }
        }
        $strsql .= "ORDER BY publish_date DESC "
            . "";
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
            $rsp['drafts'] = $rc['posts'];
        } else {
            $rsp['drafts'] = array();
        }
    }

    //
    // Get the list of upcoming posts
    //
    if( isset($args['upcoming']) && $args['upcoming'] == 'yes' ) {
        $strsql = "SELECT id, title, "
            . "publish_date, "
            . "publish_date AS publish_time, "
            . "excerpt "
            . "FROM ciniki_blog_posts "
            . "WHERE status = 40 "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND publish_date > UTC_TIMESTAMP() "
            . "";
        if( isset($args['blogtype']) && $args['blogtype'] != '' ) {
            switch($args['blogtype']) {
                case 'blog': $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 "; break;
                case 'memberblog': $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 "; break;
            }
        }
        $strsql .= "ORDER BY publish_date ASC "
            . "";
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
            $rsp['upcoming'] = $rc['posts'];
        } else {
            $rsp['upcoming'] = array();
        }
    }

    //
    // Get the list of most recent published posts
    //
    if( isset($args['past']) && ($args['past'] == 'yes' || $args['past'] > 0) ) {
        $strsql = "SELECT id, title, "
            . "publish_date, "
            . "publish_date AS publish_time, "
            . "excerpt "
            . "FROM ciniki_blog_posts "
            . "WHERE status = 40 "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND publish_date <= UTC_TIMESTAMP() "
            . "";
        if( isset($args['blogtype']) && $args['blogtype'] != '' ) {
            switch($args['blogtype']) {
                case 'blog': $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 "; break;
                case 'memberblog': $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 "; break;
            }
        }
        $strsql .= "ORDER BY publish_date DESC "
            . "";
        if( $args['past'] > 0 ) {
            $strsql .= "LIMIT " . $args['past'] . " ";
        } else {
            $strsql .= "LIMIT 25";
        }
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
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND publish_date <> '0000-00-00 00:00:00' "
//          . "AND (status = 40 || status = 10) "
            . "";
        if( isset($args['blogtype']) && $args['blogtype'] != '' ) {
            switch($args['blogtype']) {
                case 'blog': $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 "; break;
                case 'memberblog': $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 "; break;
            }
        }
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
            array('container'=>'posts', 'fname'=>'min_publish_date', 'name'=>'stats',
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
            $rsp['min_year'] = $rc['posts'][0]['stats']['min_publish_date'];
            $rsp['max_year'] = $rc['posts'][0]['stats']['max_publish_date'];
        } else {
            $rsp['min_year'] = date('Y');
            $rsp['max_year'] = date('Y');
        }
    }

    return $rsp;
}
?>
