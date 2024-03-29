<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_blog_hooks_uiSettings($ciniki, $tnid, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());

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
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.blog', 0x08) ) {
            $menu_item['label'] = 'News';
        }
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

    if( isset($ciniki['tenant']['modules']['ciniki.blog']) && isset($ciniki['tenant']['modules']['ciniki.mail']) 
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>3600, 'label'=>'Blog', 'edit'=>array('app'=>'ciniki.blog.settings'));
    }

    return $rsp;
}
?>
