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
function ciniki_blog_web_tagDetails($ciniki, $settings, $business_id, $args, $blogtype) {

    //
    // Get the tag name for use in titles
    //
    $rsp = array('stat'=>'ok');
    if( isset($args['tag_type']) && $args['tag_type'] != '' && isset($args['tag_permalink']) && $args['tag_permalink'] != '' ) {
        $strsql = "SELECT tag_name, permalink " 
            . "FROM ciniki_blog_post_tags "
            . "WHERE ciniki_blog_post_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_blog_post_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
            . "AND ciniki_blog_post_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['tag_permalink']) . "' "
            . "LIMIT 1"
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'tag');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['tag']) ) {
            $rsp['tag'] = $rc['tag'];
        } else {
            $rsp['tag'] = '';
        }
    }

    return $rsp;
}
?>
