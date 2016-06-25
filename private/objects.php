<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_blog_objects($ciniki) {
    
    $objects = array();
    $objects['post'] = array(
        'name'=>'Post',
        'sync'=>'yes',
        'table'=>'ciniki_blog_posts',
        'fields'=>array(
            'title'=>array(),
            'subtitle'=>array('default'=>''),
            'permalink'=>array(),
            'format'=>array(),
            'excerpt'=>array(),
            'content'=>array(),
            'primary_image_id'=>array('ref'=>'ciniki.images.image'),
            'primary_image_caption'=>array('name'=>'Primary Image Caption', 'default'=>''),
            'status'=>array(),
            'publish_to'=>array(),
            'publish_date'=>array(),
            'publish_year'=>array(),
            'publish_month'=>array(),
            'user_id'=>array('ref'=>'ciniki.users.user'),
            ),
        'history_table'=>'ciniki_blog_history',
        );
    $objects['tag'] = array(
        'name'=>'Post Tag',
        'sync'=>'yes',
        'table'=>'ciniki_blog_post_tags',
        'fields'=>array(
            'post_id'=>array('ref'=>'ciniki.blog.post'),
            'tag_type'=>array(),
            'tag_name'=>array(),
            'permalink'=>array(),
            ),
        'history_table'=>'ciniki_blog_history',
        );
    $objects['postimage'] = array(
        'name'=>'Post Image',
        'sync'=>'yes',
        'table'=>'ciniki_blog_post_images',
        'fields'=>array(
            'post_id'=>array('ref'=>'ciniki.blog.post'),
            'name'=>array(),
            'permalink'=>array(),
            'sequence'=>array(),
            'image_id'=>array('ref'=>'ciniki.images.image'),
            'description'=>array(),
            ),
        'history_table'=>'ciniki_blog_history',
        );
    $objects['postlink'] = array(
        'name'=>'Post Link',
        'sync'=>'yes',
        'table'=>'ciniki_blog_post_links',
        'fields'=>array(
            'post_id'=>array('ref'=>'ciniki.blog.post'),
            'name'=>array(),
            'url'=>array(),
            'description'=>array(),
            ),
        'history_table'=>'ciniki_blog_history',
        );
    $objects['postfile'] = array(
        'name'=>'Post File',
        'sync'=>'yes',
        'table'=>'ciniki_blog_post_files',
        'fields'=>array(
            'post_id'=>array('ref'=>'ciniki.blog.post'),
            'extension'=>array(),
            'name'=>array(),
            'permalink'=>array(),
            'description'=>array(),
            'org_filename'=>array(),
            'binary_content'=>array('history'=>'no'),
            ),
        'history_table'=>'ciniki_blog_history',
        );
    $objects['postref'] = array(
        'name'=>'Post Reference',
        'sync'=>'yes',
        'table'=>'ciniki_blog_post_refs',
        'fields'=>array(
            'post_id'=>array('ref'=>'ciniki.blog.post'),
            'object'=>array(),
            'object_id'=>array('oref'=>'object'),
            ),
        'history_table'=>'ciniki_blog_history',
        );
    $objects['post_subscription'] = array(
        'name'=>'Post Subscription',
        'sync'=>'yes',
        'table'=>'ciniki_blog_post_subscriptions',
        'fields'=>array(
            'post_id'=>array('ref'=>'ciniki.blog.post'),
            'subscription_id'=>array('ref'=>'ciniki.subscriptions.subscription'),
            'status'=>array(),
            ),
        'history_table'=>'ciniki_blog_history',
        );
    $objects['setting'] = array(
        'type'=>'settings',
        'name'=>'Blog Settings',
        'table'=>'ciniki_blog_settings',
        'history_table'=>'ciniki_blog_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
