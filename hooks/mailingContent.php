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
function ciniki_blog_hooks_mailingContent($ciniki, $tnid, $args) {

    if( isset($args['object']) && $args['object'] == 'ciniki.blog.post' 
        && isset($args['object_id']) && $args['object_id'] != '' 
        && isset($ciniki['tenant']['modules']['ciniki.blog']['flags'])
        && ($ciniki['tenant']['modules']['ciniki.blog']['flags']&0x7000) > 0      // Blog subscriptions enabled
        ) {
        //
        // Load INTL settings
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
        $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $intl_timezone = $rc['settings']['intl-default-timezone'];
//      $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//      $intl_currency = $rc['settings']['intl-default-currency'];

        //
        // Load the blog settings
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'settings');
        $rc = ciniki_blog_settings($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $settings = $rc['settings'];

        //
        // Load the post details
        //
        $strsql = "SELECT ciniki_blog_posts.id, "
            . "ciniki_blog_posts.title, "
            . "permalink, "
            . "format, "
            . "excerpt, "
            . "content, "
            . "primary_image_id, "
            . "status, status AS status_text, "
            . "publish_date AS publish_datetime, "
            . "publish_date, "
            . "publish_date AS publish_time "
            . "FROM ciniki_blog_posts "
            . "WHERE ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_blog_posts.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND (ciniki_blog_posts.publish_to&0x01) > 0 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
            array('container'=>'posts', 'fname'=>'id',
                'fields'=>array('id', 'title', 'subject'=>'title', 'permalink', 'format', 'synopsis'=>'excerpt', 'content', 
                    'image_id'=>'primary_image_id', 'status', 'status_text', 
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.3', 'msg'=>"I'm sorry, but we can't find the post you requested."));
        }
        $post = array_pop($rc['posts']);

        if( isset($settings['mailing-subject-prepend']) && $settings['mailing-subject-prepend'] != '' ) {
            $post['subject'] = $settings['mailing-subject-prepend'] . $post['subject'];
        }

        //
        // Build the link back text/url
        //
        if( $post['content'] != '' ) {
            $post['linkback'] = array('text'=>'View full article online', 'url'=>'/blog/' . $post['permalink']);
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
        $strsql = "SELECT id, name, extension, permalink, description, binary_content "
            . "FROM ciniki_blog_post_files "
            . "WHERE ciniki_blog_post_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_blog_post_files.post_id = '" . ciniki_core_dbQuote($ciniki, $post['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
            array('container'=>'files', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'extension', 'permalink', 'description', 'binary_content')),
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
            array('container'=>'links', 'fname'=>'id', 
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

        return array('stat'=>'ok', 'object'=>$post);
    }

    return array('stat'=>'ok');
}
?>
