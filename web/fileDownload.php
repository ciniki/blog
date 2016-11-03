<?php
//
// Description
// ===========
// This function will return the file details and content so it can be sent to the client.
//
// Returns
// -------
//
function ciniki_blog_web_fileDownload($ciniki, $business_id, $post_permalink, $file_permalink, $blogtype) {

    //
    // Get the file details
    //
    $strsql = "SELECT ciniki_blog_post_files.id, "
        . "ciniki_blog_post_files.name, "
        . "ciniki_blog_post_files.permalink, "
        . "ciniki_blog_post_files.extension, "
        . "ciniki_blog_post_files.binary_content "
        . "FROM ciniki_blog_posts, ciniki_blog_post_files "
        . "WHERE ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_blog_posts.permalink = '" . ciniki_core_dbQuote($ciniki, $post_permalink) . "' "
        . "AND ciniki_blog_posts.id = ciniki_blog_post_files.post_id "
        . "AND ciniki_blog_post_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND CONCAT_WS('.', ciniki_blog_post_files.permalink, ciniki_blog_post_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_permalink) . "' "
        . "";
    if( $blogtype == 'memberblog' ) {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x04) > 0 ";
    } else {
        $strsql .= "AND (ciniki_blog_posts.publish_to&0x01) > 0 ";
    }
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.blog.46', 'msg'=>'Unable to find requested file'));
    }
    $rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
