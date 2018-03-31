<?php
//
// Description
// -----------
// This function returns the list of objects and object_ids that should be indexed on the website.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get blog for.
//
// Returns
// -------
//
function ciniki_blog_hooks_webIndexList($ciniki, $tnid, $args) {

    $objects = array();

    //
    // Get the list of items that should be in the index
    //
    $strsql = "SELECT CONCAT('ciniki.blog.post.', id) AS oid, 'ciniki.blog.post' AS object, id AS object_id "
        . "FROM ciniki_blog_posts "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND status = 40 "
        . "AND publish_to = 0x01 "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'objects', 'fname'=>'oid', 'fields'=>array('object', 'object_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['objects']) ) {
        $objects = $rc['objects'];
    }

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
