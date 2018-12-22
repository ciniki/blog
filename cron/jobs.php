<?php
//
// Description
// ===========
// This cron job checks for any blog audio files that need processing.
//
// Arguments
// =========
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_blog_cron_jobs(&$ciniki) {
    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for blog jobs', 'severity'=>'5'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'processAudio');

    //
    // Get any blog audio files that need to be processed
    //
    $strsql = "SELECT id, uuid, tnid "
        . "FROM ciniki_blog_post_audio "
        . "WHERE (flags&0x02) = 0x02 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'audio');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) ) {
        return array('stat'=>'ok');
    }
    $files = $rc['rows'];
    
    foreach($files as $file) {
        $rc = ciniki_blog_processAudio($ciniki, $file['tnid'], $file['id']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $file['tnid'], array('code'=>'ciniki.blog.70', 'msg'=>'Unable to process audio file: ' . $file['id'],
                'cron_id'=>0, 'severity'=>50, 'err'=>$rc['err'],
                ));
        }
    }

    return array('stat'=>'ok');
}
?>
