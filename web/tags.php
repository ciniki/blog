<?php
//
// Description
// -----------
// This function will return a list of categories for the web blog page.
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
function ciniki_blog_web_tags($ciniki, $settings, $tnid, $tag_type, $blogtype) {

    $strsql = "SELECT ciniki_blog_post_tags.tag_type, "
        . "ciniki_blog_post_tags.tag_name, "
        . "ciniki_blog_post_tags.permalink, "
        . "COUNT(ciniki_blog_post_tags.post_id) AS num_tags, "
        . "MAX(ciniki_blog_posts.primary_image_id) AS image_id "
        . "FROM ciniki_blog_post_tags, ciniki_blog_posts "
        . "WHERE ciniki_blog_post_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( $tag_type > 0 ) {
        $strsql .= "AND ciniki_blog_post_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $tag_type) . "' ";
    }
    $strsql .= "AND ciniki_blog_post_tags.post_id = ciniki_blog_posts.id "
        . "AND ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        }
    } else {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
        if( isset($settings['page-blog-num-past-months']) && $settings['page-blog-num-past-months'] > 0 
            && (!isset($args['year']) && !isset($args['month'])) 
            ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            $dt->sub(new DateInterval('P' . preg_replace('/[^0-9]/', '', $settings['page-blog-num-past-months']) . 'M'));
            $strsql .= "AND ciniki_blog_posts.publish_date > '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' ";
        }
    }
    $strsql .= "GROUP BY ciniki_blog_post_tags.tag_type, ciniki_blog_post_tags.tag_name "
        . "ORDER BY ciniki_blog_post_tags.tag_type, ciniki_blog_post_tags.tag_name, ciniki_blog_posts.primary_image_id ASC, ciniki_blog_posts.date_added DESC "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'types', 'fname'=>'tag_type', 
            'fields'=>array('type'=>'tag_type')),
        array('container'=>'tags', 'fname'=>'permalink', 
            'fields'=>array('name'=>'tag_name', 'permalink', 'num_tags', 'image_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['types']) ) {
        return array('stat'=>'ok');
    }
    $types = $rc['types'];

    return array('stat'=>'ok', 'types'=>$types);    
}
?>
