<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_blog_postAudioUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'post_audio_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post Audio'),
        'post_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Post'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'checkAccess');
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postAudioUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_blog_post_audio "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['post_audio_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.64', 'msg'=>'You already have an post audio with this name, please choose another.'));
        }
    }

    //
    // Get the current details
    //
    $strsql = "SELECT ciniki_blog_post_audio.id, "
        . "ciniki_blog_post_audio.uuid, "
        . "ciniki_blog_post_audio.post_id, "
        . "ciniki_blog_post_audio.name, "
        . "ciniki_blog_post_audio.permalink, "
        . "ciniki_blog_post_audio.sequence, "
        . "ciniki_blog_post_audio.flags, "
        . "ciniki_blog_post_audio.mp3_audio_id, "
        . "ciniki_blog_post_audio.wav_audio_id, "
        . "ciniki_blog_post_audio.ogg_audio_id, "
        . "ciniki_blog_post_audio.description "
        . "FROM ciniki_blog_post_audio "
        . "WHERE ciniki_blog_post_audio.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_blog_post_audio.id = '" . ciniki_core_dbQuote($ciniki, $args['post_audio_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.69', 'msg'=>'Audio not found', 'err'=>$rc['err']));
    }
    $audio = $rc['item'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.blog');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check to see if an audio was uploaded
    //
    if( isset($_FILES['uploadfile']) ) {    
        //
        // Remove the current file
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'storageFileDelete');
        $rc = ciniki_core_storageFileDelete($ciniki, $args['tnid'], 'ciniki.blog.audio', array(
            'uuid' => $audio['uuid'],
            'subdir' => 'audio',
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.74', 'msg'=>'Unable to save upload', 'err'=>$rc['err']));
        }

        if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.75', 'msg'=>'Upload failed, file too large.'));
        }
        // FIXME: Add other checkes for $_FILES['uploadfile']['error']

        //
        // Check for a uploaded file
        //
        if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.76', 'msg'=>'Upload failed, no audio file specified.'));
        }
        $uploaded_file = $_FILES['uploadfile']['tmp_name'];

        if( ($audio['flags']&0x02) == 0 ) {
            $args['flags'] = (isset($args['flags']) ? $args['flags'] : $audio['flags']) | 0x02;
        }

        //
        // Copy the uploaded file into ciniki-storage
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'storageFileAdd');
        $rc = ciniki_core_storageFileAdd($ciniki, $args['tnid'], 'ciniki.blog.audio', array(
            'subdir' => 'audio',
            'uuid' => $audio['uuid'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.77', 'msg'=>'Unable to save upload', 'err'=>$rc['err']));
        }
        $args['uuid'] = $rc['uuid'];
    }

    //
    // Update the Post Audio in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.blog.postaudio', $args['post_audio_id'], $args, 0x04);
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.blog.postAudio', 'object_id'=>$args['post_audio_id']));

    return array('stat'=>'ok');
}
?>
