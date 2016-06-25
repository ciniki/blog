<?php
//
// Description
// -----------
// This function will check if the user has access to the blog module.  
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_blog_checkAccess(&$ciniki, $business_id, $method) {
    //
    // Check if the business is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
    $rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'blog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    if( !isset($rc['ruleset']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1548', 'msg'=>'No permissions granted'));
    }

    //
    // Sysadmins are allowed full access
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok', 'modules'=>$modules);
    }

    //
    // Users who are an owner or employee of a business can see the business blog
    //
    $strsql = "SELECT business_id, user_id FROM ciniki_business_users "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND package = 'ciniki' "
        . "AND status = 10 "
        . "AND (permission_group = 'owners' OR permission_group = 'employees' OR permission_group = 'resellers' ) "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1549', 'msg'=>'Access denied.'));
    }
    //
    // If the user has permission, return ok
    //
    if( isset($rc['rows']) && isset($rc['rows'][0]) 
        && $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
        return array('stat'=>'ok', 'modules'=>$modules);
    }

    //
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1550', 'msg'=>'Access denied'));
}
?>
