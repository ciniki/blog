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
// type:            The type of the tag.
//
//
// Returns
// -------
//
function ciniki_blog_web_tagCloud($ciniki, $settings, $business_id, $type, $blogtype) {

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
    // Build the query to get the tags
    $strsql = "SELECT ciniki_blog_post_tags.tag_name, "
        . "ciniki_blog_post_tags.permalink, "
        . "COUNT(ciniki_blog_posts.id) AS num_tags "
        . "FROM ciniki_blog_post_tags, ciniki_blog_posts "
        . "WHERE ciniki_blog_post_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_blog_post_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $type) . "' "
        . "AND ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
        . "AND ciniki_blog_posts.status = 40 "
        . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
        . "";
    if( $blogtype == 'memberblog' ) {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 ";
    } else {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
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
