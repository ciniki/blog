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
// tnid:     The ID of the tenant to get events for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_blog_web_ciListPosts($ciniki, $settings, $tnid, $args, $blogtype) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
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
        . "IF(ciniki_blog_posts.content<>'','yes','no') AS is_details "
        . "";

    if( isset($args['latest']) && $args['latest'] == 'yes' ) {
        $strsql .= ", 'unknown' AS tag_name "
            . "FROM ciniki_blog_posts "
            . "WHERE ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_blog_posts.status = 40 "
            . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
            . "";
    } elseif( isset($args['collection_id']) && $args['collection_id'] > 0 ) {
        $strsql .= ", 'unknown' AS tag_name "
            . "FROM ciniki_web_collection_objrefs "
            . "INNER JOIN ciniki_blog_posts ON ("
                . "ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_blog_posts.status = 40 "
                . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
                . ") "
            . "WHERE ciniki_web_collection_objrefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_web_collection_objrefs.collection_id = '" . ciniki_core_dbQuote($ciniki, $args['collection_id']) . "' "
            . "AND ciniki_web_collection_objrefs.object = 'ciniki.blog.post' "
            . "";
    } elseif( isset($args['tag_type']) && $args['tag_type'] != '' && isset($args['tag_permalink']) && $args['tag_permalink'] != '' ) {
        $strsql .= ", ciniki_blog_post_tags.tag_name "
            . "FROM ciniki_blog_post_tags "
            . "LEFT JOIN ciniki_blog_posts ON (ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
                . "AND ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_blog_posts.status = 40 "
                . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
                . ") "
            . "WHERE ciniki_blog_post_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_blog_post_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
            . "AND ciniki_blog_post_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['tag_permalink']) . "' "
            . "";
    } elseif( isset($args['category']) && $args['category'] != '' ) {
        $strsql .= ", ciniki_blog_post_tags.tag_name "
            . "FROM ciniki_blog_post_tags "
            . "LEFT JOIN ciniki_blog_posts ON (ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
                . "AND ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_blog_posts.status = 40 "
                . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
                . ") "
            . "WHERE ciniki_blog_post_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_blog_post_tags.tag_type = 10 "
            . "AND ciniki_blog_post_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "";
    } elseif( isset($args['tag']) && $args['tag'] != '' ) {
        $strsql .= ", ciniki_blog_post_tags.tag_name "
            . "FROM ciniki_blog_post_tags "
            . "LEFT JOIN ciniki_blog_posts ON (ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
                . "AND ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_blog_posts.status = 40 "
                . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
                . ") "
            . "WHERE ciniki_blog_post_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
            . "WHERE ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_blog_posts.status = 40 "
            . "AND ciniki_blog_posts.publish_date >= '" . $start_date->format('Y-m-d H:i:s') . "' "
            . "AND ciniki_blog_posts.publish_date < '" . $end_date->format('Y-m-d H:i:s') . "' "
            . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
            . "";
    } else {
        $strsql .= ", 'unknown' AS tag_name "
            . "FROM ciniki_blog_posts "
            . "WHERE ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_blog_posts.status = 40 "
            . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
            . "";
    }

    if( $blogtype == 'memberblog' ) {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 ";
        if( isset($settings['page-memberblog-num-past-months']) && $settings['page-memberblog-num-past-months'] > 0 ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $dt->sub(new DateInterval('P' . preg_replace('/[^0-9]/', '', $settings['page-memberblog-num-past-months']) . 'M'));
            $strsql .= "AND ciniki_blog_posts.publish_date > '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' ";
        }
    } else {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
        if( isset($settings['page-blog-num-past-months']) && $settings['page-blog-num-past-months'] > 0 ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $dt->sub(new DateInterval('P' . preg_replace('/[^0-9]/', '', $settings['page-blog-num-past-months']) . 'M'));
            $strsql .= "AND ciniki_blog_posts.publish_date > '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' ";
        }
    }

    $strsql .= "ORDER BY ciniki_blog_posts.publish_date DESC ";
    if( isset($args['offset']) && $args['offset'] > 0 && $args['offset'] < 1000000000
        && isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . intval($args['offset']) . ', ' . intval($args['limit']);
    } elseif( isset($args['limit']) && $args['limit'] > 0 && $args['limit'] < 1000000000 ) {
        $strsql .= "LIMIT " . intval($args['limit']);
    }

    //
    // Get the list of posts, sorted by publish_date for use in the web CI List Categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
//  $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', '');
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
//  if( isset($rc['rows']) ) {
//      $posts = $rc['rows'];
    if( isset($rc['posts']) ) {
        $posts = $rc['posts'];
    } else {
        $posts = array();
    }

    return array('stat'=>'ok', 'posts'=>$posts);
}
?>
