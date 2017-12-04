<?php
//
// Description
// -----------
// This function will return a list of years and months with counts of posts
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_blog_web_archive($ciniki, $settings, $tnid, $blogtype) {

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
    $strsql = "SELECT CONCAT_WS('-', ciniki_blog_posts.publish_year, ciniki_blog_posts.publish_month) AS id, "
        . "ciniki_blog_posts.publish_year, "
        . "ciniki_blog_posts.publish_month, "
        . "COUNT(ciniki_blog_posts.id) AS num_posts "
        . "FROM ciniki_blog_posts "
        . "WHERE ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_blog_posts.status = 40 "
        . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
        . "";
    if( $blogtype == 'memberblog' ) {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 ";
    } else {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
    }
    $strsql .= "GROUP BY publish_year, publish_month "
        . "ORDER BY publish_year DESC, publish_month "
        . "";
    //
    // Get the list of posts, sorted by publish_date for use in the web CI List Categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'archive', 'fname'=>'id', 
            'fields'=>array('year'=>'publish_year', 'month'=>'publish_month', 'num_posts')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['archive']) ) {
        $archive = $rc['archive'];
    } else {
        $archive = array();
    }

    return array('stat'=>'ok', 'archive'=>$archive);
}
?>
