<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_blog_web_postDetails($ciniki, $settings, $tnid, $args) {

    $modules = array();
    if( isset($ciniki['tenant']['modules']) ) {
        $modules = $ciniki['tenant']['modules'];
    }

    //
    // Load INTL settings
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
    // Load the post details
    //
    $strsql = "SELECT ciniki_blog_posts.id, "
        . "ciniki_blog_posts.title, "
        . "ciniki_blog_posts.subtitle, "
        . "permalink, "
        . "format, "
//      . "excerpt, "
        . "excerpt AS synopsis, "
        . "content, "
        . "primary_image_id, "
        . "primary_image_caption AS image_caption, "
        . "status, status AS status_text, "
        . "publish_date AS publish_datetime, "
        . "publish_date, "
        . "publish_date AS publish_time "
        . "FROM ciniki_blog_posts "
        . "WHERE ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['id']) ) {
        $strsql .= "AND ciniki_blog_posts.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' ";
    } else {
        $strsql .= "AND ciniki_blog_posts.permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' ";
    }
    if( $args['blogtype'] == 'memberblog' ) {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 ";
    } else {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'posts', 'fname'=>'id',
            'fields'=>array('id', 'title', 'subtitle', 'permalink', 'format', 'synopsis', 'content', 
                'image_id'=>'primary_image_id', 'image_caption', 'status', 'status_text', 
                'publish_datetime', 'publish_date', 'publish_time'),
            'utctotz'=>array(
                'publish_datetime'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
                'publish_date'=>array('timezone'=>$intl_timezone, 'format'=>'M j, Y'),
                'publish_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:i A'),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['posts']) || count($rc['posts']) < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.47', 'msg'=>"I'm sorry, but we can't find the post you requested."));
    }
    $post = array_pop($rc['posts']);

    //
    // Get the categories and tags for the post
    //
    if( ($modules['ciniki.blog']['flags']&0x03) > 0 ) {
        $strsql = "SELECT id, tag_type, tag_name, permalink "
            . "FROM ciniki_blog_post_tags "
            . "WHERE post_id = '" . ciniki_core_dbQuote($ciniki, $post['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY tag_type, tag_name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
            array('container'=>'types', 'fname'=>'tag_type',
                'fields'=>array('type'=>'tag_type')),
            array('container'=>'tags', 'fname'=>'id',
                'fields'=>array('id', 'name'=>'tag_name', 'permalink')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['types']) ) {
            foreach($rc['types'] as $type) {
                if( $type['type'] == 10 ) {
                    $post['categories'] = $type['tags'];
                } elseif( $type['type'] == 20 ) {
                    $post['tags'] = $type['tags'];
                }
            }
        }
    }

    //
    // Get the images for the post
    //
    $strsql = "SELECT ciniki_blog_post_images.id, "
        . "ciniki_blog_post_images.image_id, "
        . "ciniki_blog_post_images.name, "
        . "ciniki_blog_post_images.permalink, "
        . "ciniki_blog_post_images.sequence, "
        . "ciniki_blog_post_images.description, "
        . "UNIX_TIMESTAMP(ciniki_blog_post_images.last_updated) AS last_updated "
        . "FROM ciniki_blog_post_images "
        . "WHERE ciniki_blog_post_images.post_id = '" . ciniki_core_dbQuote($ciniki, $post['id']) . "' "
        . "AND ciniki_blog_post_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_blog_post_images.image_id > 0 "   // Only get images that have a picture
        . "ORDER BY ciniki_blog_post_images.sequence, ciniki_blog_post_images.date_added, "
            . "ciniki_blog_post_images.name "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'images', 'fname'=>'id',
            'fields'=>array('id', 'image_id', 'title'=>'name', 'permalink', 
                'sequence', 'description', 'last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['images']) ) {
        $post['images'] = $rc['images'];
    } else {
        $post['images'] = array();
    }

    //
    // Check if any files are attached to the post
    //
    $strsql = "SELECT id, name, extension, permalink, description "
        . "FROM ciniki_blog_post_files "
        . "WHERE ciniki_blog_post_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_blog_post_files.post_id = '" . ciniki_core_dbQuote($ciniki, $post['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'files', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'extension', 'permalink', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['files']) ) {
        $post['files'] = $rc['files'];
    }

    //
    // Get the links for the post
    //
    $strsql = "SELECT id, name, url, description "
        . "FROM ciniki_blog_post_links "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_blog_post_links.post_id = '" . ciniki_core_dbQuote($ciniki, $post['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'links', 'fname'=>'id', 'name'=>'link',
            'fields'=>array('id', 'name', 'url', 'description')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['links']) ) {
        $post['links'] = $rc['links'];
    } else {
        $post['links'] = array();
    }

    return array('stat'=>'ok', 'post'=>$post);
}
?>
