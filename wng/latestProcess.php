<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get post for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_blog_wng_latestProcess(&$ciniki, $tnid, $request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.blog']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.88', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.89', 'msg'=>"No blog specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Check for blog item request
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] != '' 
        ) {
        $request['cur_uri_pos']++;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'wng', 'postProcess');
        return ciniki_blog_wng_postProcess($ciniki, $tnid, $request, $section);
    }

    //
    // Check for image format
    //
    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($s['thumbnail-format']) && $s['thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $s['thumbnail-format'];
        if( isset($s['thumbnail-padding-color']) && $s['thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $s['thumbnail-padding-color'];
        } 
    }

    //
    // Get the list of latest posts
    //
    $strsql = "SELECT posts.id, "
        . "posts.publish_date, "
        . "DATE_FORMAT(posts.publish_date, '%M %D, %Y') AS posted, "
        . "posts.title, "
        . "posts.subtitle, "
        . "posts.permalink, "
        . "posts.primary_image_id AS image_id, "
        . "posts.excerpt AS synopsis, "
        . "IF(posts.content<>'','yes','no') AS is_details "
        . "FROM ciniki_blog_posts AS posts "
        . "";
    if( isset($s['category']) && $s['category'] != '' ) {
        $strsql .= "INNER JOIN ciniki_blog_post_tags AS tags ON ("
            . "posts.id = tags.post_id "
            . "AND tags.tag_name = '" . ciniki_core_dbQuote($ciniki, $s['category']) . "' "
            . "AND tags.tag_type = 10 "
            . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    }
    $strsql .= "WHERE posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND posts.status = 40 "
        . "AND posts.publish_date < UTC_TIMESTAMP() "
        . "AND (posts.publish_to&0x01) = 0x01 "
        . "ORDER BY posts.publish_date DESC, posts.id "
        . "";
    if( isset($s['limit']) && $s['limit'] != '' && is_numeric($s['limit']) ) {
        $strsql .= "LIMIT " . $s['limit'];
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'posts', 'fname'=>'id', 
            'fields'=>array(
                'id', 'publish_date', 'title', 'subtitle', 'posted', 'permalink', 'image_id', 'synopsis', 'is_details'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.90', 'msg'=>'Unable to load posts', 'err'=>$rc['err']));
    }
    $posts = isset($rc['posts']) ? $rc['posts'] : array();
    foreach($posts as $pid => $post) {  
        $posts[$pid]['image-id'] = $post['image_id'];
        if( !isset($s['show-date']) || $s['show-date'] == 'yes' ) {
            $posts[$pid]['meta'] = 'Posted: ' . $post['posted'];
        }
    }

    $padding = '';
    if( isset($s['thumbnail-format']) && $s['thumbnail-format'] == 'square-padded' && isset($s['thumbnail-padding-color']) ) {
        $padding = $s['thumbnail-padding-color'];
    }
    $base_url = $request['base_url'] . $request['page']['path'];
    foreach($posts as $pid => $post) {
        $posts[$pid]['url'] = $request['page']['path'] . '/' . $post['permalink'];
        $posts[$pid]['button-class'] = isset($s['button-class']) && $s['button-class'] != '' ? $s['button-class'] : 'button';
        $posts[$pid]['button-1-text'] = isset($s['button-text']) && $s['button-text'] != '' ? $s['button-text'] : 'read more';
        $posts[$pid]['button-1-url'] = $request['page']['path'] . '/' . $post['permalink'];
    }
    $blocks[] = array(
        'type' => 'tradingcards',
        'padding' => $padding,
        'items' => $posts,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
