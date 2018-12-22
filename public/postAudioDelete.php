<?php
//
// Description
// -----------
// This method will delete an post audio.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the post audio is attached to.
// post_audio_id:            The ID of the post audio to be removed.
//
// Returns
// -------
//
function ciniki_blog_postAudioDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'post_audio_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Post Audio'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'checkAccess');
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postAudioDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the post audio
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_blog_post_audio "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['post_audio_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'audio');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['audio']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.59', 'msg'=>'Post Audio does not exist.'));
    }
    $audio = $rc['audio'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.blog.postAudio', $args['post_audio_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.60', 'msg'=>'Unable to check if the post audio is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.61', 'msg'=>'The post audio is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.blog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Delete the uploaded file into ciniki-storage
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'storageFileDelete');
    $rc = ciniki_core_storageFileDelete($ciniki, $args['tnid'], 'ciniki.blog.audio', array(
        'uuid' => $audio['uuid'],
        'subdir' => 'audio',
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.78', 'msg'=>'Unable to save upload', 'err'=>$rc['err']));
    }

    //
    // Remove the audio
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.blog.postaudio',
        $args['post_audio_id'], $audio['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.blog');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.blog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'blog');

    return array('stat'=>'ok');
}
?>
