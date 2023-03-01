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

    //
    // FIXME: Add links
    //
    if( isset($post['links']) && count($post['links']) > 0 ) {
        $content .= "\n\n";
        foreach($post['links'] as $link) {
            if( $link['url'] != '' ) {
                if( isset($link['url'][0]) && $link['url'][0] != '/' ) {
                    $content .= "<a class='link' target='_blank' href='{$link['url']}'>"
                        . ($link['name'] != '' ? $link['name'] : $link['url'])
                        . "</a>\n";
                } else {
                    $content .= "<a class='link' target='_blank' href='{$request['ssl_domain_base_url']}{$link['url']}'>"
                        . ($link['name'] != '' ? $link['name'] : $link['url'])
                        . "</a>\n";
                }
            }
        }
    }

    //
    // Add files
    //
    if( isset($post['files']) && count($post['files']) > 0 ) {
        $content .= "\n\n";
        foreach($post['files'] as $file) {
            if( $file['permalink'] != '' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'urlProcess');
                $rc = ciniki_wng_urlProcess($ciniki, $tnid, $request, 0, $request['page']['path'] . '/' . $post['permalink'] . '/file/' . $file['permalink']);
                if( $rc['stat'] == 'ok' ) {
                    $content .= "<a class='link' target='_blank' href='{$rc['url']}'>{$file['name']}</a>\n";
                }
            }
        }
    }

    //
    // Check if image selected
    //
    if( isset($request['uri_split'][($request['cur_uri_pos'] + 2)])
        && $request['uri_split'][($request['cur_uri_pos'] + 1)] == 'gallery'
        && $request['uri_split'][($request['cur_uri_pos'] + 2)] != ''
        && isset($post['images'][$request['uri_split'][($request['cur_uri_pos'] + 2)]])  // Check requested image exists
        ) {
        $image = $post['images'][$request['uri_split'][($request['cur_uri_pos'] + 2)]];
        $blocks[] = array(
            'type' => 'image',
            'title' => $post['title'] . ($image['title'] != '' ? ' - ' . $image['title'] : ''),
            'image-id' => $image['image-id'],
            'image-list' => $post['images'],
            'image-permalink' => $image['permalink'],
            'base-url' => $request['page']['path'] . '/' . $post['permalink'] . '/gallery',
            );
        return array('stat'=>'ok', 'clear'=>'yes', 'last'=>'yes', 'blocks'=>$blocks);
    }
    elseif( isset($request['uri_split'][($request['cur_uri_pos'] + 2)])
        && $request['uri_split'][($request['cur_uri_pos'] + 1)] == 'file'
        && $request['uri_split'][($request['cur_uri_pos'] + 2)] != ''
        && isset($post['files'][$request['uri_split'][($request['cur_uri_pos'] + 2)]])  // Check requested file exists
        ) {
        $post_permalink = $request['uri_split'][($request['cur_uri_pos'])];
        $file_permalink = $request['uri_split'][($request['cur_uri_pos'] + 2)];

        //
        // Generate download
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'fileDownload');
        $rc = ciniki_blog_web_fileDownload($ciniki, $tnid, $post_permalink, $file_permalink, '');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.98', 'msg'=>'Unable to download file', 'err'=>$rc['err']));
        }
        $file = $rc['file'];

        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        $file = $rc['file'];
        if( $file['extension'] == 'pdf' ) {
            header('Content-Type: application/pdf');
        }
        header('Content-Length: ' . strlen($file['binary_content']));
        header('Cache-Control: max-age=0');

        print $file['binary_content'];
        return array('stat'=>'exit');
    }
    elseif( $post['image_id'] != '' && $post['image_id'] > 0 && $content != '' ) {
        $blocks[] = array(
            'type' => 'contentphoto',
            'sequence' => 1,
            'image-id' => $post['image_id'],
            'image-position' => 'top-right',
            'class' => 'limit-width center',
            'title' => $post['title'],
            'content' => $content,
            );
//        return array('stat'=>'ok', 'clear'=>'yes', 'last'=>'yes', 'blocks'=>$blocks);
    } 
    elseif( $post['image_id'] != '' && $post['image_id'] > 0 ) {
        $blocks[] = array(
            'type' => 'image',
            'sequence' => 1,
            'class' => 'limit-width center',
            'image-id' => $post['image_id'],
            'title' => $post['title'],
            );
//        return array('stat'=>'ok', 'clear'=>'yes', 'last'=>'yes', 'blocks'=>$blocks);
    } 
    else {
        $blocks[] = array(
            'type' => 'text',
            'title' => $post['title'],
            'content' => $content,
            );

    }


    //
    // Check if images
    //
    if( isset($post['images']) && count($post['images']) > 0 ) {
        foreach($post['images'] as $iid => $image) {
            $post['images'][$iid]['url'] = $request['page']['path'] . '/' . $post['permalink'] . '/gallery/' . $image['permalink'];
        }
        $blocks[] = array(
            'type' => 'gallery',
            'title' => 'Additional Images',
            'class' => 'limit-width',
            'items' => $post['images'],
            );
    }


    return array('stat'=>'ok', 'clear'=>'yes', 'last'=>'yes', 'blocks'=>$blocks);
}
?>
