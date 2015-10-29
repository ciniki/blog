<?php
//
// Description
// -----------
// This function will process a web request for the blog module.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get post for.
//
// args:			The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_blog_web_processRequest(&$ciniki, $settings, $business_id, $args) {

	if( !isset($ciniki['business']['modules']['ciniki.blog']) ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2604', 'msg'=>"I'm sorry, the page you requested does not exist."));
	}
	$page = array(
		'title'=>$args['page_title'],
		'breadcrumbs'=>$args['breadcrumbs'],
		'blocks'=>array(),
		);
	if( !isset($args['blogtype']) || $args['blogtype'] == '' ) {
		$args['blogtype'] = 'blog';
	}

	//
	// Setup the various tag types that will turn into menus
	//
	if( $args['blogtype'] == 'memberblog' ) {
		$tag_types = array(
			'category'=>array('name'=>'Categories', 'tag_type'=>'10', 'visible'=>($ciniki['business']['modules']['ciniki.blog']['flags']&0x0200)>0?'yes':'no'),
			'tag'=>array('name'=>'Tags', 'tag_type'=>'20', 'visible'=>($ciniki['business']['modules']['ciniki.blog']['flags']&0x0400)>0?'yes':'no'),
			);
	} else {
		$tag_types = array(
			'category'=>array('name'=>'Categories', 'tag_type'=>'10', 'visible'=>($ciniki['business']['modules']['ciniki.blog']['flags']&0x02)>0?'yes':'no'),
			'tag'=>array('name'=>'Tags', 'tag_type'=>'20', 'visible'=>($ciniki['business']['modules']['ciniki.blog']['flags']&0x04)>0?'yes':'no'),
			);
	}

	//
	// Check if a file was specified to be downloaded
	//
	$download_err = '';
	if( isset($args['uri_split'][0]) && $args['uri_split'][0] != ''
		&& isset($args['uri_split'][1]) && $args['uri_split'][1] == 'download'
		&& isset($args['uri_split'][2]) && $args['uri_split'][2] != '' 
		&& preg_match("/^(.*)\.pdf$/", $args['uri_split'][2], $matches)
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'downloadPDF');
		$rc = ciniki_blog_web_downloadPDF($ciniki, $settings, $business_id, $ciniki['request']['uri_split'][0], $args['uri_split'][2], $args['blogtype']);
		if( $rc['stat'] == 'ok' ) {
			return array('stat'=>'ok', 'download'=>$rc['file']);
		}
		
		//
		// If there was an error locating the files, display generic error
		//
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2606', 'msg'=>'The file you requested does not exist.'));
	}

	if( !isset($args['post_limit']) || $args['post_limit'] == '' ) {
		$page_post_limit = 10;
	} else {
		$page_post_limit = $args['post_limit'];
	}
	if( isset($ciniki['request']['args']['page']) && $ciniki['request']['args']['page'] != '' ) {
		$page_post_cur = $ciniki['request']['args']['page'];
	} else {
		$page_post_cur = 1;
	}

	//
	// Setup titles
	//
	if( $page['title'] == '' ) {
		if( $args['blogtype'] == 'memberblog' ) {
			if( isset($settings['page-memberblog-name']) && $settings['page-memberblog-name'] != '' ) {
				$page['title'] = $settings['page-memberblog-name'];
			} else {
				$page['title'] = "Member News";
			}
		} elseif( isset($settings['page-blog-name']) && $settings['page-blog-name'] != '' ) {
			$page['title'] = $settings['page-blog-name'];
		}
	}
	if( count($page['breadcrumbs']) == 0 ) {
		$page['breadcrumbs'][] = array('name'=>'Blog', 'url'=>$args['base_url']);
	}

	$display = '';
	$ciniki['response']['head']['og']['url'] = $args['domain_base_url'];

	//
	// Parse the url to determine what was requested
	//
	
	//
	// Setup the base url as the base url for this page. This may be altered below
	// as the uri_split is processed, but we do not want to alter the original passed in.
	//
	$base_url = $args['base_url']; // . "/" . $args['blogtype'];

	//
	// Check if we are to display an image, from the gallery, or latest images
	//
	$display = '';
	if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'archive' ) {
		$display = 'archive';
		$year = '';
		//
		// Show the archive for a specified year
		//
		if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' 
			&& preg_match("/^[0-9][0-9][0-9][0-9]$/", $ciniki['request']['uri_split'][1])
			) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');

			$year = $ciniki['request']['uri_split'][1];
			if( isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][2] != '' 
				&& preg_match("/^[0-9][0-9]$/", $ciniki['request']['uri_split'][2]) 
				) {
				$month = $ciniki['request']['uri_split'][2];
//				$nav_base_url = $base_url . '/' . $ciniki['request']['uri_split'][0] 
//					. '/' . $ciniki['request']['uri_split'][1] . '/' . $ciniki['request']['uri_split'][2];
			} else {
				$month = '';
//				$nav_base_url = $base_url . '/' . $ciniki['request']['uri_split'][0] 
//					. '/' . $ciniki['request']['uri_split'][1];
			}
		} 
		
	}
	elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] != '' 
		&& isset($tag_types[$args['uri_split'][0]]) && $tag_types[$args['uri_split'][0]]['visible'] == 'yes'
		) {
		$type_permalink = $args['uri_split'][0];
		$tag_type = $tag_types[$type_permalink]['tag_type'];
		$tag_title = $tag_types[$type_permalink]['name'];
		$display = 'type';
		$base_url .= '/' . $type_permalink;

		//
		// Check if post was specified
		//
		if( isset($args['uri_split'][1]) && $args['uri_split'][1] != '' 
			&& isset($args['uri_split'][2]) && $args['uri_split'][2] != '' 
			) {
			$tag_permalink = $args['uri_split']['1'];
			$post_permalink = $args['uri_split']['2'];
			$display = 'post';
			$ciniki['response']['head']['links'][] = array('rel'=>'canonical', 
				'href'=>$args['domain_base_url'] . '/' . $post_permalink);
			$ciniki['response']['head']['og']['url'] .= '/' . $post_permalink;
			$base_url .= '/' . $tag_permalink . '/' . $post_permalink;
			
			//
			// Check for gallery pic request
			//
			if( isset($args['uri_split'][3]) && $args['uri_split'][3] == 'gallery' 
				&& isset($args['uri_split'][4]) && $args['uri_split'][4] != '' 
				) {
				$image_permalink = $args['uri_split'][4];
				$display = 'postpic';
			}
		} 

		//
		// Check if tag name was specified
		//
		elseif( isset($args['uri_split'][1]) && $args['uri_split'][1] != '' ) {
			$tag_type = $tag_types[$args['uri_split'][0]]['tag_type'];
			$tag_title = $tag_types[$args['uri_split'][0]]['name'];
			$tag_permalink = $args['uri_split']['1'];
			$display = 'tag';
			$ciniki['response']['head']['og']['url'] .= '/' . $type_permalink . '/' . $tag_permalink;
			$base_url .= '/' . $tag_permalink;
		}
		//
		// Setup type og 
		//
		else {
			$ciniki['response']['head']['og']['url'] .= '/' . $type_permalink;
		}
	}

	//
	// Check if post url request without tag path
	//
	elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] != '' ) {
		$post_permalink = $args['uri_split'][0];
		$display = 'post';
		//
		// Check for gallery pic request
		//
		if( isset($args['uri_split'][1]) && $args['uri_split'][1] == 'gallery'
			&& isset($args['uri_split'][2]) && $args['uri_split'][2] != '' 
			) {
			$image_permalink = $args['uri_split'][2];
			$display = 'postpic';
		}
		$ciniki['response']['head']['og']['url'] .= '/' . $post_permalink;
		$base_url .= '/' . $post_permalink;
	}

	//
	// Nothing selected, default to first tag type
	//
	else {
		$display = 'latest';
	}

/*
		//
		// Get the items for the specified category
		//
		if( $ciniki['request']['uri_split'][0] == 'category' ) {
			$rc = ciniki_blog_web_posts($ciniki, $settings, $ciniki['request']['business_id'], 
				array('category'=>urldecode($ciniki['request']['uri_split'][1]), 
					'offset'=>(($page_post_cur-1)*$page_post_limit), 'limit'=>$page_post_limit+1), $args['blogtype']);
		} else {
			$rc = ciniki_blog_web_posts($ciniki, $settings, $ciniki['request']['business_id'], 
				array('tag'=>urldecode($ciniki['request']['uri_split'][1]), 
					'offset'=>(($page_post_cur-1)*$page_post_limit), 'limit'=>$page_post_limit+1), $args['blogtype']);
		}
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$posts = $rc['posts'];
	
		//
		// Get the tag name
		//
		$tag_name = $ciniki['request']['uri_split'][1];
		foreach($posts as $post) {
			$tag_name = $post['tag_name'];
			break;
		}
		$page_title .= ' - ' . $tag_name;

		$page_content .= "<article class='page'>\n"
			. "<header class='entry-title'><h1 id='entry-title' class='entry-title'>$page_title</h1></header>\n"
			. "<div class='entry-content'>\n"
			. "";

		//
		// Generate list of posts
		//
		$nav_base_url = $ciniki['request']['base_url'] . "/$blogtype/" . $ciniki['request']['uri_split'][0] 
			. '/' . $ciniki['request']['uri_split'][1];
		if( count($posts) > 0 ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processCIList');
			$rc = ciniki_web_processCIList($ciniki, $settings, $base_url, $posts, 
				array('page'=>$page_post_cur, 'limit'=>$page_post_limit,
					'prev'=>'Newer Posts &rarr;',
					'next'=>'&larr; Older Posts',
					'base_url'=>$nav_base_url,
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= $rc['content'];
			$nav_content = $rc['nav'];
		} else {
			$page_content .= "<p>Currently no posts.</p>";
		}
		$page_content .= "</div>";
		$page_content .= "</article>";
		if( $nav_content != '' ) {
			$page_content .= $nav_content;
		}
	}

	//
	// Display list of categories or tags
	//
	elseif( isset($ciniki['request']['uri_split'][0]) 
		&& ($ciniki['request']['uri_split'][0] == 'categories' || $ciniki['request']['uri_split'][0] == 'tags') 
		) {

		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'tagCloud');
		if( $ciniki['request']['uri_split'][0] == 'categories' ) {
			$page_title .= ' - Categories';
			$base_url = $ciniki['request']['base_url'] . "/$blogtype/category";
			$rc = ciniki_blog_web_tagCloud($ciniki, $settings, $ciniki['request']['business_id'], 10, $blogtype);
		} elseif( $ciniki['request']['uri_split'][0] == 'tags' ) {
			$page_title .= ' - Tags';
			$base_url = $ciniki['request']['base_url'] . "/$blogtype/tag";
			$rc = ciniki_blog_web_tagCloud($ciniki, $settings, $ciniki['request']['business_id'], 20, $blogtype);
		}
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Process the tags
		//
		if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processTagCloud');
			$rc = ciniki_web_processTagCloud($ciniki, $settings, $base_url, $rc['tags']);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= $rc['content'];
		} else {
			if( $ciniki['request']['uri_split'][0] == 'categories' ) {
				$page_content = "<p>I'm sorry, there are no categories for this blog";
			} elseif( $ciniki['request']['uri_split'][0] == 'tags' ) {
				$page_content = "<p>I'm sorry, there are no tags for this blog";
			}
		}
	}

	//
	// Display the archive of month posts
	//
	elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'archive'
		&& isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' 
		&& preg_match("/^[0-9][0-9][0-9][0-9]$/", $ciniki['request']['uri_split'][1])
		) {

		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');

		$year = $ciniki['request']['uri_split'][1];
		if( isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][2] != '' 
			&& preg_match("/^[0-9][0-9]$/", $ciniki['request']['uri_split'][2]) 
			) {
			$month = $ciniki['request']['uri_split'][2];
			$page_title .= ' - ' . date_format(date_create($year . '-' . $month . '-01'), 'M Y');
			$nav_base_url = $ciniki['request']['base_url'] . "/$blogtype/" . $ciniki['request']['uri_split'][0] 
				. '/' . $ciniki['request']['uri_split'][1] . '/' . $ciniki['request']['uri_split'][2];
		} else {
			$month = '';
			$page_title .= ' - ' . $year;
			$nav_base_url = $ciniki['request']['base_url'] . "/$blogtype/" . $ciniki['request']['uri_split'][0] 
				. '/' . $ciniki['request']['uri_split'][1];
		}

		$rc = ciniki_blog_web_posts($ciniki, $settings, $ciniki['request']['business_id'], 
			array('year'=>$year, 'month'=>$month, 
				'offset'=>(($page_post_cur-1)*$page_post_limit), 'limit'=>$page_post_limit+1), $blogtype);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$posts = $rc['posts'];
	
		$page_content .= "<article class='page'>\n"
			. "<header class='entry-title'><h1 id='entry-title' class='entry-title'>$page_title</h1></header>\n"
			. "<div class='entry-content'>\n"
			. "";

		//
		// Generate list of posts
		//
		if( count($posts) > 0 ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processCIList');
			$rc = ciniki_web_processCIList($ciniki, $settings, $base_url, $posts, 
				array('page'=>$page_post_cur, 'limit'=>$page_post_limit,
					'prev'=>'Newer Posts &rarr;',
					'next'=>'&larr; Older Posts',
					'base_url'=>$nav_base_url,
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= $rc['content'];
			$nav_content = $rc['nav'];
		} else {
			$page_content .= "<p>Currently no posts.</p>";
		}
		$page_content .= "</div>";
		$page_content .= "</article>";
		if( isset($nav_content) && $nav_content != '' ) {
			$page_content .= $nav_content;
		}
	}

	//
	// Display the archive of posts
	//
	elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'archive' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'archive');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');

		$rc = ciniki_blog_web_archive($ciniki, $settings, $ciniki['request']['business_id'], $blogtype);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$prev_year = '';
		$years = '';
		$months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		foreach($rc['archive'] as $m) {
			$year = $m['year'];
			$month_txt = $months[$m['month']-1];
			$month = sprintf("%02d", $m['month']);
			if( $year != $prev_year ) {
				if( $prev_year != '' ) { $years .= "</dd>"; }
				$years .= "<dt>$year</dt><dd>";
				$cm = '';
			}
			$years .= $cm . "<a href='" . $ciniki['request']['base_url'] . "/$blogtype/archive/$year/$month'>"
				. "$month_txt</a>&nbsp;(" . $m['num_posts'] . ")";
			$cm = ', ';
			$prev_year = $year;
		}

		$page_title .= ' - Archive';

		$page_content .= "<article class='page'>\n"
			. "<header class='entry-title'><h1 id='entry-title' class='entry-title'>$page_title</h1></header>\n"
			. "<div class='entry-content'>\n"
			. "";

		if( $years != '' ) {
			$page_content .= "<dl class='wide'>$years</dl>";
		} else {
			$page_content .= "<p>Currently no posts.</p>";
		}

		$page_content .= "</div></article>";
	}

	//
	// Display the page of the post details
	//
	elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != '' ) {

		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'postDetails');
		//
		// Get the post information
		//
		$post_permalink = $ciniki['request']['uri_split'][0];
		$ciniki['response']['head']['og']['url'] .= '/' . $post_permalink;
		$rc = ciniki_blog_web_postDetails($ciniki, $settings, 
			$ciniki['request']['business_id'], array('permalink'=>$post_permalink, 'blogtype'=>$blogtype));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$post = $rc['post'];
		$page_title = $post['title'];

		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processBlogPost');
		$rc = ciniki_web_processBlogPost($ciniki, $settings, $post, 
			array('blogtype'=>$blogtype, 'output'=>'web'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$page_content .= $rc['content'];
	}
*/

/*
		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
		$rc = ciniki_blog_web_posts($ciniki, $settings, $ciniki['request']['business_id'], 
			array('offset'=>(($page_post_cur-1)*$page_post_limit), 'limit'=>$page_post_limit+1), $args['blogtype']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['posts']) || count($rc['posts']) < 1 ) {
			$page_content .= "<p>Currently no posts.</p>";
		} else {
			$posts = $rc['posts'];
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processCIList');
			$base_url = $ciniki['request']['base_url'] . '/' . $args['blogtype'];
			$rc = ciniki_web_processCIList($ciniki, $settings, $base_url, $posts, 
				array('page'=>$page_post_cur, 'limit'=>$page_post_limit,
					'prev'=>'Newer Posts &rarr;',
					'next'=>'&larr; Older Posts',
					'base_url'=>$ciniki['request']['base_url'] . '/' . $args['blogtype'],
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= $rc['content'];
			$nav_content = $rc['nav'];
		}
		$page_content .= "</div></article>";
		if( isset($nav_content) && $nav_content != '' ) {
			$page_content .= $nav_content;
		}
*/	
	//
	// Get the content to display
	//
	if( $display == 'latest' || $display == 'tag' || ($display == 'archive' && isset($year) && $year != '') ) {
		if( $display == 'latest' ) {
			//
			// Get the items for the specified category
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
			$rc = ciniki_blog_web_posts($ciniki, $settings, $business_id, array('latest'=>'yes'), $args['blogtype']);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$posts = $rc['posts'];
		} elseif( $display == 'archive' ) {
			$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
			$page['breadcrumbs'][] = array('name'=>'Archive', 'url'=>$args['base_url'] . '/archive');
			$page['breadcrumbs'][] = array('name'=>$year, 'url'=>$args['base_url'] . '/archive/'. $year);
			if( $month != '' ) {
				$page['title'] = $months[intval($month)-1] . ' ' . $year;
				$page['breadcrumbs'][] = array('name'=>$months[intval($month)-1], 'url'=>$args['base_url'] . '/archive/'. $year . '/' . $month);
			} else {
				$page['title'] = $year;
			}

			//
			// Get the items for the specified category
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
			$rc = ciniki_blog_web_posts($ciniki, $settings, $business_id, array('year'=>$year, 'month'=>$month, 
					'offset'=>(($page_post_cur-1)*$page_post_limit), 'limit'=>$page_post_limit+1), $args['blogtype']);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$posts = $rc['posts'];

			
		} else {
			//
			// Get the tag name and permalink
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'tagDetails');
			$rc = ciniki_blog_web_tagDetails($ciniki, $settings, $business_id, array('tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink), $args['blogtype']);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$tag_name = $ciniki['request']['uri_split'][1];
			$tag_name = $rc['tag']['tag_name'];
			$page['title'] = $tag_name;
			$page['breadcrumbs'][] = array('name'=>$tag_types[$type_permalink]['name'], 'url'=>$args['base_url'] . '/' . $type_permalink);
			$page['breadcrumbs'][] = array('name'=>$tag_name, 'url'=>$args['base_url'] . '/' . $type_permalink . '/' . urlencode($rc['tag']['permalink']));

			//
			// Get the items for the specified category
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
			$rc = ciniki_blog_web_posts($ciniki, $settings, $business_id, array('tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink), $args['blogtype']);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$posts = $rc['posts'];

//			$page['title'] .= ($page['title']!=''?' - ':'') . $tag_name;
		}
		if( count($posts) > 0 ) {
			//
			// Setup meta data
			//
			foreach($posts as $pid => $post) {
				$posts[$pid]['meta'] = array();
				if( isset($post['publish_date']) && $post['publish_date'] != '' ) {
					$posts[$pid]['meta']['date'] = $post['publish_date'];
				}
				if( isset($post['categories']) && count($post['categories']) > 0 ) {	
					$posts[$pid]['meta']['categories'] = $post['categories'];
				}
				$posts[$pid]['meta']['category_base_url'] = $args['base_url'] . '/category';
				$posts[$pid]['meta']['divider'] = (isset($settings['page-blog-meta-divider'])?$settings['page-blog-meta-divider']:' | ');
				$posts[$pid]['meta']['category_prefix'] = (isset($settings['page-blog-meta-category-prefix'])?$settings['page-blog-meta-category-prefix']:'');
				$posts[$pid]['meta']['categories_prefix'] = (isset($settings['page-blog-meta-categories-prefix'])?$settings['page-blog-meta-categories-prefix']:'');
			}
			
			//
			// Setup the listing block
			//
			$page['blocks'][] = array('type'=>'imagelist', 
				'image_version'=>((isset($settings['page-blog-list-image-version'])&&$settings['page-blog-list-image-version']=='original')?'original':'thumbnail'),
				'image_width'=>'600',
				'more_button_text'=>(isset($settings['page-blog-more-button-text'])?$settings['page-blog-more-button-text']:''),
				'base_url'=>$base_url, 'noimage'=>'yes', 'list'=>$posts);
		} else {
			$page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but there doesn't seem to be any posts available.");
		}
	}

	elseif( $display == 'archive' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'archive');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');

		$rc = ciniki_blog_web_archive($ciniki, $settings, $ciniki['request']['business_id'], $args['blogtype']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		$page['title'] = 'Archive';
		$page['breadcrumbs'][] = array('name'=>'Archive', 'url'=>$args['base_url'] . '/archive');

		if( count($rc['archive']) > 0 ) {
			$page['blocks'][] = array('type'=>'archivelist', 
				'base_url'=>$args['base_url'] . '/archive',
				'archive'=>$rc['archive']);
		} else {
			$page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but there doesn't seem to be any posts archived.");
		}
	}

	elseif( $display == 'type' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'tags');
		$rc = ciniki_blog_web_tags($ciniki, $settings, $ciniki['request']['business_id'], $tag_type, $args['blogtype']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['types'][$tag_type]['tags']) ) {
			$tags = $rc['types'][$tag_type]['tags'];
		} else {
			$tags = array();
		}
	
//		$page['title'] .= ($page['title']!=''?' - ':'') . $tag_types[$type_permalink]['name'];
		$page['title'] = $tag_types[$type_permalink]['name'];
		$page['breadcrumbs'][] = array('name'=>$tag_types[$type_permalink]['name'], 'url'=>$args['base_url'] . '/' . $type_permalink);

		if( count($tags) > 25 || $tag_type == '20' ) {
			$page['blocks'][] = array('type'=>'tagcloud', 'base_url'=>$base_url, 'tags'=>$tags);
		} elseif( count($tags) > 0 ) {
			$page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url, 'tags'=>$tags);
		} else {
			$page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but we don't have any posts for that category.");
		}
	} 

	elseif( $display == 'post' || $display == 'postpic' ) {
		if( isset($tag_type) && isset($type_permalink) ) {
			//
			// Get the tag name and permalink
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'tagDetails');
			$rc = ciniki_blog_web_tagDetails($ciniki, $settings, $business_id, array('tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink), $args['blogtype']);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$tag_name = $ciniki['request']['uri_split'][1];
			$tag_name = $rc['tag']['tag_name'];
			$page['breadcrumbs'][] = array('name'=>$tag_types[$type_permalink]['name'], 'url'=>$args['base_url'] . '/' . $type_permalink);
			$page['breadcrumbs'][] = array('name'=>$tag_name, 'url'=>$args['base_url'] . '/' . $type_permalink . '/' . urlencode($rc['tag']['permalink']));
		}

		//
		// Load the post to get all the details, and the list of images.
		// It's one query, and we can find the requested image, and figure out next
		// and prev from the list of images returned
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'postDetails');
		$rc = ciniki_blog_web_postDetails($ciniki, $settings, $ciniki['request']['business_id'], array('permalink'=>$post_permalink, 'blogtype'=>$args['blogtype']));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$post = $rc['post'];

		if( isset($post['synopsis']) && $post['synopsis'] != '' ) {
			$ciniki['response']['head']['og']['description'] = strip_tags($post['synopsis']);
		} elseif( isset($post['content']) && $post['content'] != '' ) {
			$ciniki['response']['head']['og']['description'] = strip_tags($post['content']);
		}

//		$page['title'] .= ($page['title']!=''?' - ':'') . $post['title'];
		$page['title'] = $post['title'];
		if( isset($post['subtitle']) ) {
			$page['subtitle'] = $post['subtitle'];
		}
		$page['meta'] = array();
		$page['meta']['date'] = $post['publish_date'];
		$page['meta']['categories'] = $post['categories'];
		$page['meta']['divider'] = (isset($settings['page-blog-meta-divider'])?$settings['page-blog-meta-divider']:' | ');
		$page['meta']['category_base_url'] = $args['base_url'] . '/category';
		$page['meta']['category_prefix'] = (isset($settings['page-blog-meta-category-prefix'])?$settings['page-blog-meta-category-prefix']:'');
		$page['meta']['categories_prefix'] = (isset($settings['page-blog-meta-categories-prefix'])?$settings['page-blog-meta-categories-prefix']:'');

		$page['breadcrumbs'][] = array('name'=>$post['title'], 'url'=>$base_url);

		if( $display == 'postpic' ) {
			if( !isset($post['images']) || count($post['images']) < 1 ) {
				$page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
			} else {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'galleryFindNextPrev');
				$rc = ciniki_web_galleryFindNextPrev($ciniki, $post['images'], $image_permalink);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( $rc['img'] == NULL ) {
					$page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
				} else {
					$page['breadcrumbs'][] = array('name'=>$rc['img']['title'], 'url'=>$base_url . '/gallery/' . $image_permalink);
					if( $rc['img']['title'] != '' ) {
						$page['title'] .= ' - ' . $rc['img']['title'];
					}
					$block = array('type'=>'galleryimage', 'primary'=>'yes', 'image'=>$rc['img']);
					if( $rc['prev'] != null ) {
						$block['prev'] = array('url'=>$base_url . '/gallery/' . $rc['prev']['permalink'], 'image_id'=>$rc['prev']['image_id']);
					}
					if( $rc['next'] != null ) {
						$block['next'] = array('url'=>$base_url . '/gallery/' . $rc['next']['permalink'], 'image_id'=>$rc['next']['image_id']);
					}
					$page['blocks'][] = $block;
				}
			}

		} else {
			$page['container_class'] = 'ciniki-blog-post';
			if( isset($post['image_id']) && $post['image_id'] > 0 ) {
				if( isset($settings['page-blog-sidebar']) && $settings['page-blog-sidebar'] == 'yes' ) { 
					$page['blocks'][] = array('type'=>'image', 'primary'=>'yes', 'image_id'=>$post['image_id'], 'title'=>'');
				} else {
					$page['blocks'][] = array('type'=>'asideimage', 'primary'=>'yes', 'image_id'=>$post['image_id'], 'title'=>$post['title'], 'caption'=>'');
				}
			}
			if( isset($post['content']) && $post['content'] != '' ) {
				$page['blocks'][] = array('type'=>'content', 'title'=>'', 'content'=>$post['content']);
			}
			if( isset($post['files']) && count($post['files']) > 0 ) {
				$page['blocks'][] = array('type'=>'files', 'title'=>'', 'base_url'=>$base_url . '/download/', 'files'=>$post['files']);
			}
			if( isset($post['links']) && count($post['links']) > 0 ) {
				$page['blocks'][] = array('type'=>'links', 'title'=>'', 'links'=>$post['links']);
			}
			// FIXME: Include meta information
			if( $args['blogtype'] == 'blog' && (!isset($settings['page-blog-share-buttons']) || $settings['page-blog-share-buttons'] == 'yes') ) {
				$tags = array();
				$page['blocks'][] = array('type'=>'sharebuttons', 'title'=>$post['title'], 'tags'=>$tags);
			}
			if( isset($post['images']) && count($post['images']) > 0 ) {
				$page['blocks'][] = array('type'=>'gallery', 'title'=>'Additional Images', 'base_url'=>$base_url . '/gallery', 'images'=>$post['images'])    ;
			}
		}

	}

	//
	// Return error if nothing found to display
	//
	else {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2592', 'msg'=>"We're sorry, the page you requested."));
	}

	//
	// Return nothing if the page format doesn't have a submenu
	//
	if( !isset($settings['page-blog-submenu']) || $settings['page-blog-submenu'] == 'yes' ) {
		//
		// The submenu 
		//
		$page['submenu'] = array();
		$page['submenu']['latest'] = array('name'=>'Latest', 'url'=>$args['base_url']);
		$page['submenu']['archive'] = array('name'=>'Archive', 'url'=>$args['base_url'] . '/archive');
		foreach($tag_types as $tag_permalink => $tag) {
			if( $tag['visible'] == 'yes' ) {
				$page['submenu'][$tag_permalink] = array('name'=>$tag['name'], 'url'=>$args['base_url'] . '/' . $tag_permalink);
			}
		}
	}

	//
	// Setup the sidebar
	//
	if( isset($settings['page-blog-sidebar']) && $settings['page-blog-sidebar'] == 'yes' ) { 
		$page['sidebar'] = array();	

		//
		// Get the latest posts
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
		$rc = ciniki_blog_web_posts($ciniki, $settings, $business_id, array('latest'=>'yes', 'limit'=>3), $args['blogtype']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$posts = $rc['posts'];
		if( count($posts) > 0 ) {
			//
			// Setup meta data
			//
			foreach($posts as $pid => $post) {
				$posts[$pid]['meta'] = array();
				if( isset($post['publish_date']) && $post['publish_date'] != '' ) {
					$posts[$pid]['meta']['date'] = $post['publish_date'];
				}
			}
			$page['sidebar'][] = array('type'=>'imagelist', 'title'=>'Latest posts',
				'image_version'=>((isset($settings['page-blog-list-image-version'])&&$settings['page-blog-list-image-version']=='original')?'original':'thumbnail'),
				'image_width'=>'400',
				'more_button_text'=>(isset($settings['page-blog-more-button-text'])?$settings['page-blog-more-button-text']:''),
				'base_url'=>$base_url, 'noimage'=>'yes', 'list'=>$posts);
		}

		//
		// Get the list of tags
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'tags');
		$rc = ciniki_blog_web_tags($ciniki, $settings, $ciniki['request']['business_id'], 0, $args['blogtype']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['types'][10]['tags']) ) {
			$page['sidebar'][] = array('type'=>'taglist', 'title'=>'Categories', 'tag_type'=>'categories', 'base_url'=>$args['base_url'] . '/category', 'tags'=>$rc['types'][10]['tags']);
		}
		if( isset($rc['types'][20]['tags']) ) {
			$page['sidebar'][] = array('type'=>'taglist', 'title'=>'Keywords', 'tag_type'=>'tags', 'base_url'=>$args['base_url'] . '/tag', 'tags'=>$rc['types'][20]['tags']);
		}

	}

	return array('stat'=>'ok', 'page'=>$page);
}
?>