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
function ciniki_blog_hooks_mailingFinished($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

    // Set the default to not used
    $used = 'no';
    $count = 0;
    $msg = '';

    if( isset($args['object']) && isset($args['object_id']) 
        && $args['object'] == 'ciniki.subscriptions.subscription' 
        && isset($ciniki['tenant']['modules']['ciniki.blog']['flags'])
        && ($ciniki['tenant']['modules']['ciniki.blog']['flags']&0x7000) > 0      // Blog subscriptions enabled
        ) {
        //
        // Update status of any subscriptions from 30 - sending to 50 - sent
        //
        $strsql = "SELECT ciniki_blog_post_subscriptions.id "
            . "FROM ciniki_blog_post_subscriptions "
            . "WHERE ciniki_blog_post_subscriptions.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_blog_post_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_blog_post_subscriptions.status < 50 "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
            array('container'=>'subscriptions', 'fname'=>'id',
                'fields'=>array('id')),
            ));
        $subscriptions = $rc['subscriptions'];
        foreach($subscriptions as $sid => $sub) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.blog.post_subscription', $sid, array('status'=>'50'), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
