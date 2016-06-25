<?php
//
// Description
// -----------
// This function returns the array of status text for ciniki_blog_posts.status.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_blog_maps($ciniki) {

    $maps = array();
    $maps['post'] = array(
        'status'=>array(
            '10'=>'Draft',
            '40'=>'Published',
            '60'=>'Removed',
            ),
        );
    $maps['post_subscription'] = array(
        'status'=>array(
            '0'=>'Unattached',
            '10'=>'Attached',
            '30'=>'Sending',
            '50'=>'Sent',
            ),
        );
    
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
