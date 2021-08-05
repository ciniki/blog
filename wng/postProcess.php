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
function ciniki_blog_wng_postProcess(&$ciniki, $tnid, $request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.blog']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.91', 'msg'=>"I'm sorry, the page you requested does not exist."));
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
    if( !isset($request['uri_split'][$request['cur_uri_pos']])
        || $request['uri_split'][$request['cur_uri_pos']] == '' 
        ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.91', 'msg'=>"I'm sorry, the item you requested does not exist."));
    }
    $post_permalink = $request['uri_split'][$request['cur_uri_pos']];

    //
    // Load the post
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'wng', 'postLoad');
    $rc = ciniki_blog_wng_postLoad($ciniki, $tnid, $request, $post_permalink);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.92', 'msg'=>"I'm sorry, the item you requested does not exist.", 'err'=>$rc['err']));
    }
    if( !isset($rc['post']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.93', 'msg'=>"I'm sorry, the item you requested does not exist.", 'err'=>$rc['err']));
    }
    $post = $rc['post'];

    $content = $post['content'] != '' ? $post['content'] : $post['synopsis'];

    if( $post['image_id'] != '' && $post['image_id'] > 0 && $content != '' ) {
        $blocks[] = array(
            'type' => 'contentphoto',
            'sequence' => 1,
            'image-id' => $post['image_id'],
            'image-position' => 'top-right',
            'class' => 'limit-width center',
            'title' => $post['title'],
            'content' => $content,
            );
    } elseif( $post['image_id'] != '' && $post['image_id'] > 0 ) {
        $blocks[] = array(
            'type' => 'image',
            'sequence' => 1,
            'class' => 'limit-width center',
            'image-id' => $post['image_id'],
            'title' => $post['title'],
            );
    } else {
        $blocks[] = array(
            'type' => 'text',
            'title' => $post['title'],
            'content' => $content,
            );
    }

    return array('stat'=>'ok', 'clear'=>'yes', 'last'=>'yes', 'blocks'=>$blocks);
}
?>
