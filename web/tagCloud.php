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
// type:            The type of the tag.
//
//
// Returns
// -------
//
function ciniki_blog_web_tagCloud($ciniki, $settings, $tnid, $type, $blogtype) {

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
    // Build the query to get the tags
    //
    $strsql = "SELECT ciniki_blog_post_tags.tag_name, "
        . "ciniki_blog_post_tags.permalink, "
        . "COUNT(ciniki_blog_posts.id) AS num_tags "
        . "FROM ciniki_blog_post_tags, ciniki_blog_posts "
        . "WHERE ciniki_blog_post_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_blog_post_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $type) . "' "
        . "AND ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
        . "AND ciniki_blog_posts.status = 40 "
        . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
        . "";
    if( $blogtype == 'memberblog' ) {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 ";
        if( isset($settings['page-memberblog-num-past-months']) && $settings['page-memberblog-num-past-months'] > 0 
            && (!isset($args['year']) && !isset($args['month'])) 
            ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $dt->sub(new DateInterval('P' . preg_replace('/[^0-9]/', '', $settings['page-memberblog-num-past-months']) . 'M'));
            $strsql .= "AND ciniki_blog_posts.publish_date > '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' ";
            $strsql_count .= "AND ciniki_blog_posts.publish_date > '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' ";
        }
    } else {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
        if( isset($settings['page-blog-num-past-months']) && $settings['page-blog-num-past-months'] > 0 
            && (!isset($args['year']) && !isset($args['month'])) 
            ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $dt->sub(new DateInterval('P' . preg_replace('/[^0-9]/', '', $settings['page-blog-num-past-months']) . 'M'));
            $strsql .= "AND ciniki_blog_posts.publish_date > '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' ";
            $strsql_count .= "AND ciniki_blog_posts.publish_date > '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' ";
        }
    }
    $strsql .= "GROUP BY tag_name "
        . "ORDER BY tag_name "
        . "";

    //
    // Get the list of posts, sorted by publish_date for use in the web CI List Categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'tags', 'fname'=>'permalink', 
            'fields'=>array('name'=>'tag_name', 'permalink', 'num_tags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        $tags = $rc['tags'];
    } else {
        $tags = array();
    }

    return array('stat'=>'ok', 'tags'=>$tags);
}
?>
