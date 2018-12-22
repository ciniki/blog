<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_blog_processAudio(&$ciniki, $tnid, $file_id) {

    if( !isset($ciniki['config']['ciniki.core']['sox']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.73', 'msg'=>'Missing audio converter'));
    }
    $sox = $ciniki['config']['ciniki.core']['sox'];

    //
    // Get the tenant UUID
    //
    $strsql = "SELECT uuid FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.83', 'msg'=>'Unable to get tenant details'));
    }
    $tenant_uuid = $rc['tenant']['uuid'];

    //
    // Load the details of the audio file
    //
    $strsql = "SELECT ciniki_blog_post_audio.id, "
        . "ciniki_blog_post_audio.uuid, "
        . "ciniki_blog_post_audio.post_id, "
        . "ciniki_blog_post_audio.name, "
        . "ciniki_blog_post_audio.permalink, "
        . "ciniki_blog_post_audio.sequence, "
        . "ciniki_blog_post_audio.flags, "
        . "ciniki_blog_post_audio.org_filename, "
        . "ciniki_blog_post_audio.mp3_audio_id, "
        . "ciniki_blog_post_audio.wav_audio_id, "
        . "ciniki_blog_post_audio.ogg_audio_id, "
        . "ciniki_blog_post_audio.description "
        . "FROM ciniki_blog_post_audio "
        . "WHERE ciniki_blog_post_audio.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_blog_post_audio.id = '" . ciniki_core_dbQuote($ciniki, $file_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'file');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.71', 'msg'=>'Unable to load file', 'err'=>$rc['err']));
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.72', 'msg'=>'Unable to find requested file'));
    }
    $file = $rc['file'];

    //
    // Check the flags
    //
    if( ($file['flags']&0x02) == 0 ) {
        return array('stat'=>'ok');
    }

    $name = preg_replace("/^([^\/]+)\.([^\/\.]+)$/", "$1", $file['org_filename']);
    $extension = preg_replace("/^.*\.([^\.]+)$/", "$1", $file['org_filename']);
    $tmp_filename = '/tmp';
    if( isset($ciniki['config']['ciniki.core']['tmp_dir']) && $ciniki['config']['ciniki.core']['tmp_dir'] != '' ) {
        $tmp_filename = $ciniki['config']['ciniki.core']['tmp_dir'];
    }
    $wav_filename = $tmp_filename . '/' . $file['uuid'] . '.wav';
    $mp3_filename = $tmp_filename . '/' . $file['uuid'] . '.mp3';
    $ogg_filename = $tmp_filename . '/' . $file['uuid'] . '.ogg';

    //
    // Use the checksum from the main file as checksum will be different each time a file is transcoded to mp3/ogg/wav
    //
    $checksum = hash_file('md5', $tmp_filename);

    //
    // Setup filename from storage
    //
    $storage_filename = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
        . $tenant_uuid[0] . '/' . $tenant_uuid
        . "/ciniki.blog/audio/"
        . $file['uuid'][0] . '/' . $file['uuid'];

    //
    // Convert
    //
    $output = exec("$sox '$storage_filename' -r 44.1k -b 16 '$wav_filename' gain -n -1");
    $output = exec("$sox '$storage_filename' -r 44.1k -C 0.2 '$mp3_filename' gain -n -1");
    $output = exec("$sox '$storage_filename' -r 44.1k -C 10 '$ogg_filename' gain -n -1");

    //
    // Keep track of updates to the blog audio record
    //
    $update_args = array(   
        'flags' => ($file['flags']&0xFFFD),
        );

    //
    // Insert the files
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'audio', 'hooks', 'insertFromFile');
    $rc = ciniki_audio_hooks_insertFromFile($ciniki, $tnid, array(
        'filename'=>$wav_filename,
        'name'=>$name,
        'checksum'=>$checksum,
        'original_filename'=>$name . '.wav',
        ));
    if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.80', 'msg'=>'Unable to add file', 'err'=>$rc['err']));
    }
    $update_args['wav_audio_id'] = $rc['id'];

    $rc = ciniki_audio_hooks_insertFromFile($ciniki, $tnid, array(
        'filename'=>$mp3_filename,
        'name'=>$name,
        'checksum'=>$checksum,
        'original_filename'=>$name . '.mp3',
        ));
    if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.81', 'msg'=>'Unable to add file', 'err'=>$rc['err']));
    }
    $update_args['mp3_audio_id'] = $rc['id'];

    $rc = ciniki_audio_hooks_insertFromFile($ciniki, $tnid, array(
        'filename'=>$ogg_filename,
        'name'=>$name,
        'checksum'=>$checksum,
        'original_filename'=>$name . '.ogg',
        ));
    if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.82', 'msg'=>'Unable to add file', 'err'=>$rc['err']));
    }
    $update_args['ogg_audio_id'] = $rc['id'];

    //
    // Update the audio listing
    //
    if( count($update_args) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.blog.postaudio', $file_id, $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.79', 'msg'=>'Unable to update the audio', 'err'=>$rc['err']));
        }
    }
    
    return array('stat'=>'ok');
}
?>
