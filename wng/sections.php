<?php
//
// Description
// -----------
// Return the list of sections available from the blog module
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_blog_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure blog module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.blog']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.85', 'msg'=>'Module not enabled'));
    }

    //
    // Get the list of categories
    //
    $strsql = "SELECT DISTINCT tags.tag_name AS category "
        . "FROM ciniki_blog_post_tags AS tags "
        . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND tags.tag_type = 10 "
        . "ORDER BY category "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'categories', 'fname'=>'category', 
            'fields'=>array('id'=>'category', 'name'=>'category')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.94', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();
    array_unshift($categories, array('id'=>'', 'name'=>'All'));

    $sections = array();

    //
    // The latest blog section
    //
    $sections['ciniki.blog.latest'] = array(
        'name' => 'Latest',
        'module' => 'Blog',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'category' => array('label'=>'Category', 'type'=>'select', 'idnames'=>'yes', 'options'=>$categories),
            'thumbnail-format' => array('label'=>'Thumbnail Format', 'type'=>'toggle', 'default'=>'square-cropped', 
                'toggles'=>array(
                    'square-cropped' => 'Cropped',
                    'square-padded' => 'Padded',
                )),
            'thumbnail-padding-color' => array('label'=>'Padding Color', 'type'=>'colour'),
            'show-date' => array('label'=>'Show Date', 'type'=>'toggle', 'default'=>'yes', 'toggles'=>array(
                'no' => 'No',
                'yes' => 'Yes',
                )),
            'button-text' => array('label'=>'Link Text', 'type'=>'text'),
            'button-class' => array('label'=>'Link Type', 'type'=>'toggle', 'default'=>'button', 
                'toggles'=>array(
                    'button' => 'Button',
                    'link' => 'Link',
                )),
            'limit' => array('label'=>'Number of Items', 'type'=>'text', 'size'=>'small'),
            ),
        );

    if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.blog', 0x02) ) {
        unset($sections['ciniki.blog.latest']['settings']['category']);
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.blog', 0x08) ) {
        $sections['ciniki.blog.latest']['module'] = 'News';
    }

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
