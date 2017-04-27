<?php
//
// Description
// -----------
// This function will return a list of posts organized by date
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get events for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_blog_web_posts($ciniki, $settings, $business_id, $args, $blogtype) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Load the business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
//  $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//  $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Build the query string to get the posts
    //
    $strsql = "SELECT ciniki_blog_posts.id, "
        . "ciniki_blog_posts.publish_date, "
        . "ciniki_blog_posts.title, "
        . "ciniki_blog_posts.subtitle, "
        . "ciniki_blog_posts.permalink, "
        . "ciniki_blog_posts.primary_image_id AS image_id, "
        . "ciniki_blog_posts.excerpt AS synopsis, "
        . "IF(ciniki_blog_posts.content<>'','yes','no') AS is_details "
//        . "categories.id AS tag_id, "
//        . "categories.tag_name, "
//        . "categories.permalink AS tag_permalink "
        . "";

    if( isset($args['latest']) && $args['latest'] == 'yes' ) {
        $strsql .= "FROM ciniki_blog_posts "
//            . "LEFT JOIN ciniki_blog_post_tags AS categories ON ("
//                . "ciniki_blog_posts.id = categories.post_id "
//                . "AND categories.tag_type = 10 "
//                . "AND categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//                . ") "
            . "WHERE ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_blog_posts.status = 40 "
            . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
            . "";
        $strsql_count = "SELECT 'posts', COUNT(ciniki_blog_posts.id) AS posts "
            . "FROM ciniki_blog_posts "
            . "WHERE ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_blog_posts.status = 40 "
            . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
            . "";
    } elseif( isset($args['collection_id']) && $args['collection_id'] > 0 ) {
        $strsql .= "FROM ciniki_web_collection_objrefs "
            . "INNER JOIN ciniki_blog_posts ON ("
                . "ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_blog_posts.status = 40 "
                . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
                . ") "
//            . "LEFT JOIN ciniki_blog_post_tags AS categories ON ("
//                . "ciniki_blog_posts.id = categories.post_id "
//                . "AND categories.tag_type = 10 "
//                . "AND categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//                . ") "
            . "WHERE ciniki_web_collection_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_web_collection_objrefs.collection_id = '" . ciniki_core_dbQuote($ciniki, $args['collection_id']) . "' "
            . "AND ciniki_web_collection_objrefs.object = 'ciniki.blog.post' "
            . "";
        $strsql_count = "SELECT 'posts', COUNT(ciniki_blog_posts.id) AS posts "
            . "FROM ciniki_web_collection_objrefs "
            . "INNER JOIN ciniki_blog_posts ON ("
                . "ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_blog_posts.status = 40 "
                . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
                . ") "
            . "WHERE ciniki_web_collection_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_web_collection_objrefs.collection_id = '" . ciniki_core_dbQuote($ciniki, $args['collection_id']) . "' "
            . "AND ciniki_web_collection_objrefs.object = 'ciniki.blog.post' "
            . "";
    } elseif( isset($args['tag_type']) && $args['tag_type'] != '' && isset($args['tag_permalink']) && $args['tag_permalink'] != '' ) {
        $strsql .= "FROM ciniki_blog_post_tags "
            . "LEFT JOIN ciniki_blog_posts ON (ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
                . "AND ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_blog_posts.status = 40 "
                . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
                . ") "
//            . "LEFT JOIN ciniki_blog_post_tags AS categories ON ("
//                . "ciniki_blog_posts.id = categories.post_id "
//                . "AND categories.tag_type = 10 "
//                . "AND categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//                . ") "
            . "WHERE ciniki_blog_post_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_blog_post_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
            . "AND ciniki_blog_post_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['tag_permalink']) . "' "
            . "";
        $strsql_count = "SELECT 'posts', COUNT(ciniki_blog_posts.id) AS posts "
            . "FROM ciniki_blog_post_tags "
            . "LEFT JOIN ciniki_blog_posts ON (ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
                . "AND ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_blog_posts.status = 40 "
                . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
                . ") "
            . "WHERE ciniki_blog_post_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_blog_post_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
            . "AND ciniki_blog_post_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['tag_permalink']) . "' "
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

        $strsql .= "FROM ciniki_blog_posts "
//            . "LEFT JOIN ciniki_blog_post_tags AS categories ON ("
//                . "ciniki_blog_posts.id = categories.post_id "
//                . "AND categories.tag_type = 10 "
//                . "AND categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//                . ") "
            . "WHERE ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_blog_posts.status = 40 "
            . "AND ciniki_blog_posts.publish_date >= '" . $start_date->format('Y-m-d H:i:s') . "' "
            . "AND ciniki_blog_posts.publish_date < '" . $end_date->format('Y-m-d H:i:s') . "' "
            . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
            . "";
        $strsql_count = "SELECT 'posts', COUNT(ciniki_blog_posts.id) AS posts "
            . "FROM ciniki_blog_posts "
            . "WHERE ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_blog_posts.status = 40 "
            . "AND ciniki_blog_posts.publish_date >= '" . $start_date->format('Y-m-d H:i:s') . "' "
            . "AND ciniki_blog_posts.publish_date < '" . $end_date->format('Y-m-d H:i:s') . "' "
            . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
            . "";
    } else {
        $strsql .= "FROM ciniki_blog_posts "
//            . "LEFT JOIN ciniki_blog_post_tags AS categories ON ("
//                . "ciniki_blog_posts.id = categories.post_id "
//                . "AND categories.tag_type = 10 "
//                . "AND categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//                . ") "
            . "WHERE ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_blog_posts.status = 40 "
            . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
            . "";
        $strsql_count = "SELECT 'posts', COUNT(ciniki_blog_posts.id) AS posts "
            . "FROM ciniki_blog_posts "
            . "WHERE ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_blog_posts.status = 40 "
            . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
            . "";
    }

    if( $blogtype == 'memberblog' ) {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 ";
        $strsql_count .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 ";
    } else {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
        $strsql_count .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
    }

    if( isset($args['year']) && $args['year'] != '' ) {
        $strsql .= "ORDER BY ciniki_blog_posts.publish_date ASC, ciniki_blog_posts.id ";
    } else {
        $strsql .= "ORDER BY ciniki_blog_posts.publish_date DESC, ciniki_blog_posts.id ";
    }
    if( isset($args['offset']) && $args['offset'] > 0 
        && isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . $args['offset'] . ', ' . $args['limit'];
    } elseif( isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . $args['limit'];
    }
    
    //
    // Get the number of posts to be used for navigation
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql_count, 'ciniki.blog', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['num']['posts']) ) {
        $num_posts = $rc['num']['posts'];
    } else {
        $num_posts = 0;
    }

    //
    // Get the list of posts, sorted by publish_date for use in the web CI List Categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'posts', 'fname'=>'id',
            'fields'=>array('id', 'title', 'subtitle', 'permalink', 'image_id', 'synopsis', 'is_details', 'publish_date'),
            'utctotz'=>array('publish_date'=>array('timezone'=>$intl_timezone, 'format'=>'M j, Y')),
            ),
//        array('container'=>'categories', 'fname'=>'tag_id', 
//            'fields'=>array('name'=>'tag_name', 'permalink'=>'tag_permalink')),
        )); 
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['posts']) ) {
        $posts = $rc['posts'];
    } else {
        $posts = array();
    }

    //
    // Get the categories for each post
    //
    $ids = array_keys($posts);
    if( count($ids) > 0 ) {
        $strsql = "SELECT categories.post_id, "
            . "categories.id, "
            . "categories.tag_name, "
            . "categories.permalink AS tag_permalink "
            . "FROM ciniki_blog_post_tags AS categories "
            . "WHERE post_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
            . "AND categories.tag_type = 10 "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "ORDER BY post_id "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
            array('container'=>'posts', 'fname'=>'post_id', 'fields'=>array('id'=>'post_id')),
            array('container'=>'categories', 'fname'=>'id', 'fields'=>array('name'=>'tag_name', 'permalink'=>'tag_permalink')),
            )); 
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['posts']) ) {
            foreach($rc['posts'] as $post) {
                if( isset($posts[$post['id']]) ) {
                    $posts[$post['id']]['categories'] = $post['categories'];
                }
            }
        }
    }

    return array('stat'=>'ok', 'posts'=>$posts, 'total_num_posts'=>$num_posts);
}
?>
