<?php
//
// Description
// ===========
// This method will return all the information about an post audio.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the post audio is attached to.
// post_audio_id:          The ID of the post audio to get the details for.
//
// Returns
// -------
//
function ciniki_blog_postAudioGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'post_audio_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post Audio'),
        'post_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Post'),
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['tnid'], 'ciniki.blog.postAudioGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Post Audio
    //
    if( $args['post_audio_id'] == 0 ) {
        $seq = 1;
        if( isset($args['post_id']) && $args['post_id'] > 0 ) {
            $strsql = "SELECT MAX(sequence) AS seq "
                . "FROM ciniki_blog_post_audio "
                . "WHERE post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.68', 'msg'=>'Unable to get next sequence number', 'err'=>$rc['err']));
            }
            if( isset($rc['item']['seq']) ) {
                $seq = $rc['item']['seq'] + 1;
            }
        }
        $audio = array('id'=>0,
            'post_id'=>'',
            'name'=>'',
            'permalink'=>'',
            'sequence'=>$seq,
            'flags'=>0x01,
            'mp3_audio_id'=>0,
            'wav_audio_id'=>0,
            'ogg_audio_id'=>0,
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Post Audio
    //
    else {
        $strsql = "SELECT ciniki_blog_post_audio.id, "
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
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.blog', array(
            array('container'=>'audio', 'fname'=>'id', 
                'fields'=>array('post_id', 'name', 'permalink', 'sequence', 'flags', 
                    'mp3_audio_id', 'wav_audio_id', 'ogg_audio_id', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.62', 'msg'=>'Post Audio not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['audio'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.63', 'msg'=>'Unable to find Post Audio'));
        }
        $audio = $rc['audio'][0];
    }

    return array('stat'=>'ok', 'audio'=>$audio);
}
?>
