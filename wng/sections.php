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

    $sections = array();

    //
    // The latest blog section
    //
    $sections['ciniki.blog.latest'] = array(
        'name' => 'Latest',
        'module' => 'Blog',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
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

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.blog', 0x08) ) {
        $sections['ciniki.blog.latest']['module'] = 'News';
    }

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
