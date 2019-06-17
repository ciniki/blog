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
function ciniki_blog_web_webCollectionList($ciniki, $settings, $tnid, $args, $blogtype) {

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
        . "'yes' AS is_details, "
        . "'unknown' AS tag_name "
        . "FROM ciniki_web_collection_objrefs "
        . "INNER JOIN ciniki_blog_posts ON ("
            . "ciniki_web_collection_objrefs.object_id = ciniki_blog_posts.id "
            . "AND ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_blog_posts.status = 40 "
            . "AND ciniki_blog_posts.publish_date < UTC_TIMESTAMP() "
            . "";
    if( $blogtype == 'memberblog' ) {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 ";
    } else {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
    }
    $strsql .= ") "
        . "WHERE ciniki_web_collection_objrefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_web_collection_objrefs.collection_id = '" . ciniki_core_dbQuote($ciniki, $args['collection_id']) . "' "
        . "AND ciniki_web_collection_objrefs.object = 'ciniki.blog.post' "
        . "";

    $strsql .= "ORDER BY ciniki_blog_posts.publish_date DESC, id ";
    if( isset($args['offset']) && $args['offset'] > 0 
        && isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . intval($args['offset']) . ', ' . intval($args['limit']);
    } elseif( isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . intval($args['limit']);
    }

    //
    // Get the list of posts, sorted by publish_date for use in the web CI List Categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
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
    if( isset($rc['posts']) ) {
        $posts = $rc['posts'];

    } else {
        $posts = array();
    }

    return array('stat'=>'ok', 'posts'=>$posts);
}
?>
