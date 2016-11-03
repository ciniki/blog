<?php
//
// Description
// -----------
// This method returns the information about an image attached to a blog post.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the image is attached to.
// post_image_id:       The ID of the post image to get.
//
// Returns
// -------
//
function ciniki_blog_postImageGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'post_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post Image'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'checkAccess');
    $rc = ciniki_blog_checkAccess($ciniki, $args['business_id'], 'ciniki.blog.postImageGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_blog_post_images.id, "
        . "ciniki_blog_post_images.name, "
        . "ciniki_blog_post_images.permalink, "
        . "ciniki_blog_post_images.image_id, "
        . "ciniki_blog_post_images.sequence, "
        . "ciniki_blog_post_images.description "
        . "FROM ciniki_blog_post_images "
        . "WHERE ciniki_blog_post_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_blog_post_images.id = '" . ciniki_core_dbQuote($ciniki, $args['post_image_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'images', 'fname'=>'id', 'name'=>'image',
            'fields'=>array('id', 'name', 'permalink', 'sequence', 'image_id', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['images']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.blog.28', 'msg'=>'Unable to find image'));
    }
    $image = $rc['images'][0]['image'];
    
    return array('stat'=>'ok', 'image'=>$image);
}
?>
