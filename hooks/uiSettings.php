<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_blog_hooks_uiSettings($ciniki, $business_id, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array());  

    //
    // Check permissions for what menu items should be available
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.blog', 0x01)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>3603,
            'label'=>'Blog', 
            'edit'=>array('app'=>'ciniki.blog.main', 'args'=>array('blogtype'=>'"\'blog\'"')),
            );
        $rsp['menu_items'][] = $menu_item;
    } 
    //
    // Check if member blog should be a link
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.blog', 0x0100)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>3602,
            'label'=>'Member News', 
            'edit'=>array('app'=>'ciniki.blog.main', 'args'=>array('blogtype'=>'"\'memberblog\'"')),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    return $rsp;
}
?>
