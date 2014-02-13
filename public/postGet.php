<?php
//
// Description
// -----------
// This method will return the information for a post.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_blog_postGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'post_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post'),
		'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
		'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
		'links'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Links'),
		'refs'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'References'),
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
		'tags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tags'),
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
    $rc = ciniki_blog_checkAccess($ciniki, $args['business_id'], 'ciniki.blog.postGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Load currency and timezone settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

	//
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'private', 'postStatusMaps');
	$rc = ciniki_blog_postStatusMaps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$status_maps = $rc['maps'];

	//
	// Get the basic post information
	//
	$strsql = "SELECT ciniki_blog_posts.id, "
		. "ciniki_blog_posts.title, "
		. "permalink, "
		. "format, "
		. "excerpt, "
		. "content, "
		. "primary_image_id, "
		. "status, status AS status_text, "
		. "publish_date "
		. "FROM ciniki_blog_posts "
		. "WHERE ciniki_blog_posts.id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
		. "AND ciniki_blog_posts.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
		array('container'=>'posts', 'fname'=>'id', 'name'=>'post',
			'fields'=>array('id', 'title', 'permalink', 'format', 'excerpt', 'content', 
				'primary_image_id', 'status', 'status_text',
				'publish_date'),
			'utctotz'=>array('publish_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
			'maps'=>array('status_text'=>$status_maps),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['posts'][0]['post']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1560', 'msg'=>'Unable to find the requested post'));
	}
	$post = $rc['posts'][0]['post'];

	//
	// Get the categories and tags for the post
	//
	if( ($modules['ciniki.blog']['flags']&0x03) > 0 ) {
		$strsql = "SELECT tag_type, tag_name AS lists "
			. "FROM ciniki_blog_post_tags "
			. "WHERE post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY tag_type, tag_name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
			array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
				'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['tags']) ) {
			foreach($rc['tags'] as $tags) {
				if( $tags['tags']['tag_type'] == 10 ) {
					$post['categories'] = $tags['tags']['lists'];
				} elseif( $tags['tags']['tag_type'] == 20 ) {
					$post['tags'] = $tags['tags']['lists'];
				}
			}
		}
	}

	//
	// Get the images for the post
	//
	if( isset($args['images']) && $args['images'] == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
		$strsql = "SELECT ciniki_blog_post_images.id, "
			. "ciniki_blog_post_images.image_id, "
			. "ciniki_blog_post_images.name, "
			. "ciniki_blog_post_images.sequence, "
			. "ciniki_blog_post_images.description "
			. "FROM ciniki_blog_post_images "
			. "WHERE ciniki_blog_post_images.post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
			. "AND ciniki_blog_post_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY ciniki_blog_post_images.sequence, ciniki_blog_post_images.date_added, "
				. "ciniki_blog_post_images.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
			array('container'=>'images', 'fname'=>'id', 'name'=>'image',
				'fields'=>array('id', 'image_id', 'name', 'sequence', 'description')),
			));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['images']) ) {
			$post['images'] = $rc['images'];
			foreach($post['images'] as $img_id => $img) {
				if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
					$rc = ciniki_images_loadCacheThumbnail($ciniki, $args['business_id'], $img['image']['image_id'], 75);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$post['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
				}
			}
		} else {
			$post['images'] = array();
		}
	}

	//
	// Get the files for the post
	//
	if( isset($args['files']) && $args['files'] == 'yes' ) {
		$strsql = "SELECT id, name, extension, permalink "
			. "FROM ciniki_blog_post_files "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_blog_post_files.post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
			array('container'=>'files', 'fname'=>'id', 'name'=>'file',
				'fields'=>array('id', 'name', 'extension', 'permalink')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['files']) ) {
			$post['files'] = $rc['files'];
		} else {
			$post['files'] = array();
		}
	}

	//
	// Get the links for the post
	//
	if( isset($args['files']) && $args['files'] == 'yes' ) {
		$strsql = "SELECT id, name, url, description "
			. "FROM ciniki_blog_post_links "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_blog_post_links.post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
			array('container'=>'links', 'fname'=>'id', 'name'=>'link',
				'fields'=>array('id', 'name', 'url', 'description')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['links']) ) {
			$post['links'] = $rc['links'];
		} else {
			$post['links'] = array();
		}
	}

	//
	// Get any recipes that were referenced in this blog post
	//
	if( ((isset($args['refs']) && $args['refs'] == 'yes') 
		|| (isset($args['recipes']) && $args['recipes'] == 'yes'))
		&& isset($modules['ciniki.recipes']) ) {
		$strsql = "SELECT ciniki_recipes.id, "
			. "ciniki_blog_post_refs.id AS ref_id, "
			. "ciniki_recipes.name "
			. "FROM ciniki_blog_post_refs "
			. "LEFT JOIN ciniki_recipes ON (ciniki_blog_post_refs.object_id = ciniki_recipes.id "
				. "AND ciniki_recipes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_blog_post_refs.post_id = '" . ciniki_core_dbQuote($ciniki, $args['post_id']) . "' "
			. "AND ciniki_blog_post_refs.object = 'ciniki.recipes.recipe' "
			. "AND ciniki_blog_post_refs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ""; 
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
			array('container'=>'recipes', 'fname'=>'id', 'name'=>'recipe',
				'fields'=>array('id', 'ref_id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['recipes']) ) {
			$post['recipes'] = $rc['recipes'];
		}
	}

	//
	// Check if all categories should be returned
	//
	$categories = array();
	if( isset($args['categories']) && $args['categories'] == 'yes' ) {
		//
		// Get the available tags
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
		$rc = ciniki_core_tagsList($ciniki, 'ciniki.blog', $args['business_id'], 
			'ciniki_blog_post_tags', 10);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1595', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$categories = $rc['tags'];
		}
	}

	//
	// Check if all tags should be returned
	//
	$tags = array();
	if( isset($args['tags']) && $args['tags'] == 'yes' ) {
		//
		// Get the available tags
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
		$rc = ciniki_core_tagsList($ciniki, 'ciniki.blog', $args['business_id'], 
			'ciniki_blog_post_tags', 20);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1596', 'msg'=>'Unable to get list of tags', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$tags = $rc['tags'];
		}
	}

	return array('stat'=>'ok', 'post'=>$post, 'categories'=>$categories, 'tags'=>$tags);
}
?>
