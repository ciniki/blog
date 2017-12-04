<?php
//
// Description
// -----------
// This function will return the sub menu items for the dropdown menus.
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
function ciniki_blog_web_subMenuItems(&$ciniki, $settings, $tnid, $args) {
    
    if( !isset($ciniki['tenant']['modules']['ciniki.blog']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.51', 'msg'=>"I'm sorry, the file you requested does not exist."));
    }
    if( !isset($args['blogtype']) || $args['blogtype'] == '' ) {
        $args['blogtype'] = 'blog';
    }

    //
    // Return nothing if the page format doesn't have a submenu
    //
    if( isset($settings['page-blog-submenu']) && $settings['page-blog-submenu'] != 'yes' ) {
        return array('stat'=>'ok', 'submenu'=>array());
    }

    //
    // Setup the various tag types that will turn into menus
    //
    if( $args['blogtype'] == 'memberblog' ) {
        $tag_types = array(
            'category'=>array('name'=>'Categories', 'tag_type'=>'10', 'visible'=>($ciniki['tenant']['modules']['ciniki.blog']['flags']&0x0200)>0?'yes':'no'),
            'tag'=>array('name'=>'Tags', 'tag_type'=>'20', 'visible'=>($ciniki['tenant']['modules']['ciniki.blog']['flags']&0x0400)>0?'yes':'no'),
            );
    } else {
        $tag_types = array(
            'category'=>array('name'=>'Categories', 'tag_type'=>'10', 'visible'=>($ciniki['tenant']['modules']['ciniki.blog']['flags']&0x02)>0?'yes':'no'),
            'tag'=>array('name'=>'Tags', 'tag_type'=>'20', 'visible'=>($ciniki['tenant']['modules']['ciniki.blog']['flags']&0x04)>0?'yes':'no'),
            );
    }

    //
    // The submenu 
    //
    $submenu = array();
    $submenu['latest'] = array('title'=>'Latest', 'permalink'=>'');
    $submenu['archive'] = array('title'=>'Archive', 'permalink'=>'archive');
    foreach($tag_types as $tag_permalink => $tag) {
        if( $tag['visible'] == 'yes' ) {
            $submenu[$tag_permalink] = array('title'=>$tag['name'], 'permalink'=>$tag_permalink);
        }
    }

    return array('stat'=>'ok', 'submenu'=>$submenu);
}
?>
