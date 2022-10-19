<?php
//
// Description
// ===========
// This function will return the file details and content so it can be sent to the client.
//
// Returns
// -------
//
function ciniki_blog_web_fileDownload($ciniki, $tnid, $post_permalink, $file_permalink, $blogtype) {

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Get the file details
    //
    $strsql = "SELECT ciniki_blog_post_files.id, "
        . "ciniki_blog_post_files.uuid, "
        . "ciniki_blog_post_files.name, "
        . "ciniki_blog_post_files.permalink, "
        . "ciniki_blog_post_files.extension, "
        . "ciniki_blog_post_files.binary_content "
        . "FROM ciniki_blog_posts, ciniki_blog_post_files "
        . "WHERE ciniki_blog_posts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_blog_posts.permalink = '" . ciniki_core_dbQuote($ciniki, $post_permalink) . "' "
        . "AND ciniki_blog_posts.id = ciniki_blog_post_files.post_id "
        . "AND ciniki_blog_post_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND CONCAT_WS('.', ciniki_blog_post_files.permalink, ciniki_blog_post_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_permalink) . "' "
        . "";
    if( $blogtype == 'memberblog' ) {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 ";
    } else {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
    }
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'file');
    error_log(print_r($rc,true));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.blog.46', 'msg'=>'Unable to find requested file'));
    }
    $rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

    //
    // Get the storage filename
    //
    $storage_filename = $tenant_storage_dir . '/ciniki.blog/files/' . $rc['file']['uuid'][0] . '/' . $rc['file']['uuid'];
    if( file_exists($storage_filename) ) {
        $rc['file']['binary_content'] = file_get_contents($storage_filename);    
    }

    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
