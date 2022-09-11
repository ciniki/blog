<?php
//
// Description
// -----------
// This function will process a web request for the blog module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get post for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_blog_web_processRequest(&$ciniki, $settings, $tnid, $args) {

    if( !isset($ciniki['tenant']['modules']['ciniki.blog']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.48', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>($args['page_title'] != '' ? $args['page_title'] : 'Blog'),
        'breadcrumbs'=>$args['breadcrumbs'],
        'article-class'=>'ciniki-blog',
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
            'category'=>array('name'=>'Categories', 'tag_type'=>'10', 'visible'=>($ciniki['tenant']['modules']['ciniki.blog']['flags']&0x0200)>0?'yes':'no'),
            'tag'=>array('name'=>'Keywords', 'tag_type'=>'20', 'visible'=>($ciniki['tenant']['modules']['ciniki.blog']['flags']&0x0400)>0?'yes':'no'),
            );
    } else {
        $tag_types = array(
            'category'=>array('name'=>'Categories', 'tag_type'=>'10', 'visible'=>($ciniki['tenant']['modules']['ciniki.blog']['flags']&0x02)>0?'yes':'no'),
            'tag'=>array('name'=>'Keywords', 'tag_type'=>'20', 'visible'=>($ciniki['tenant']['modules']['ciniki.blog']['flags']&0x04)>0?'yes':'no'),
            );
    }

    $display_format = 'imagelist';
    if( isset($settings['site-theme']) && $settings['site-theme'] == 'twentyone' 
        && isset($settings['page-blog-display-format']) && $settings['page-blog-display-format'] == 'tradingcards' 
        ) {
        $display_format = 'tradingcards';
    }

    //
    // Check for image format
    //
    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($settings['page-blog-thumbnail-format']) && $settings['page-blog-thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $settings['page-blog-thumbnail-format'];
        if( isset($settings['page-blog-thumbnail-padding-color']) && $settings['page-blog-thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $settings['page-blog-thumbnail-padding-color'];
        } 
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
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'fileDownload');
        $rc = ciniki_blog_web_fileDownload($ciniki, $ciniki['request']['tnid'], $args['uri_split'][0], $args['uri_split'][2], $args['blogtype']);
        if( $rc['stat'] == 'ok' ) {
            return array('stat'=>'ok', 'download'=>$rc['file']);
        }
        
        //
        // If there was an error locating the files, display generic error
        //
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.49', 'msg'=>'The file you requested does not exist.'));
    }

    if( !isset($args['post_limit']) || $args['post_limit'] == '' ) {
        $page_post_limit = 10;
    } else {
        $page_post_limit = $args['post_limit'];
    }
    if( isset($ciniki['request']['args']['page']) && $ciniki['request']['args']['page'] != '' && is_numeric($ciniki['request']['args']['page']) ) {
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
        if( isset($settings['page-blog-name']) && $settings['page-blog-name'] != '' ) {
            $page['breadcrumbs'][] = array('name'=>$settings['page-blog-name'], 'url'=>$args['base_url']);
        } else {
            $page['breadcrumbs'][] = array('name'=>'Blog', 'url'=>$args['base_url']);
        }
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
    if( (isset($args['uri_split'][0]) && $args['uri_split'][0] == 'archive')
        || $args['module_page'] == 'ciniki.blog.archive'
        ) {
        $display = 'archive';
        $year = '';
        $uri_offset = 0;
        if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'archive' ) {
            $uri_offset = 1;
        }

        //
        // Show the archive for a specified year
        //
        if( isset($args['uri_split'][$uri_offset]) && $args['uri_split'][$uri_offset] != '' ) {
            if( preg_match("/^[0-9][0-9][0-9][0-9]$/", $args['uri_split'][$uri_offset]) ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');

                $year = $args['uri_split'][$uri_offset];
                if( isset($args['uri_split'][($uri_offset+1)]) && $args['uri_split'][($uri_offset+1)] != '' 
                    && preg_match("/^[0-9][0-9]$/", $args['uri_split'][($uri_offset+1)]) 
                    ) {
                    $month = $args['uri_split'][($uri_offset+1)];
                } else {
                    $month = '';
                }
            } else {
                $display = 'post';
                $post_permalink = $args['uri_split'][0];
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

    //
    // Get the content to display
    //
    if( $display == 'latest' || $display == 'tag' || ($display == 'archive' && isset($year) && $year != '') ) {
        if( $display == 'latest' ) {
            //
            // Get the items for the specified category
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
            $rc = ciniki_blog_web_posts($ciniki, $settings, $tnid, array('latest'=>'yes',
                'offset'=>(($page_post_cur-1)*$page_post_limit), 'limit'=>$page_post_limit+1), $args['blogtype']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $posts = $rc['posts'];
            $total_num_posts = $rc['total_num_posts'];
        } elseif( $display == 'archive' ) {
            $months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
            $base_url = $args['base_url'];
            if( $args['module_page'] != 'ciniki.blog.archive' ) {
                $page['breadcrumbs'][] = array('name'=>'Archive', 'url'=>$args['base_url'] . '/archive');
            } else {
                $base_url .= '/archive';
            }
            $page['breadcrumbs'][] = array('name'=>$year, 'url'=>$base_url . '/'. $year);
            if( $month != '' ) {
                $base_url = $args['base_url'] . '/' . $year . '/' . $month;
                $page['title'] = $months[intval($month)-1] . ' ' . $year;
                $page['breadcrumbs'][] = array('name'=>$months[intval($month)-1], 'url'=>$base_url . '/'. $year . '/' . $month);
            } else {
                $base_url = $base_url . '/' . $year;
                $page['title'] = $year;
            }

            //
            // Get the items for the specified category
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
            //$rc = ciniki_blog_web_posts($ciniki, $settings, $tnid, array('year'=>$year, 'month'=>$month, 
            //    'offset'=>(($page_post_cur-1)*$page_post_limit), 'limit'=>$page_post_limit+1), $args['blogtype']);
            $rc = ciniki_blog_web_posts($ciniki, $settings, $tnid, array('year'=>$year, 'month'=>$month), $args['blogtype']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $posts = $rc['posts'];
            $total_num_posts = $rc['total_num_posts'];
        } else {
            //
            // Get the tag name and permalink
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'tagDetails');
            $rc = ciniki_blog_web_tagDetails($ciniki, $settings, $tnid, array('tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink), $args['blogtype']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['tag']['tag_name']) ) {
                return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.57', 'msg'=>"We're sorry, the page you requested does not exist."));
            }
            //$tag_name = $ciniki['request']['uri_split'][1];
            $tag_name = $rc['tag']['tag_name'];
            $page['title'] = $tag_name;
            $page['breadcrumbs'][] = array('name'=>$tag_types[$type_permalink]['name'], 'url'=>$args['base_url'] . '/' . $type_permalink);
            $page['breadcrumbs'][] = array('name'=>$tag_name, 'url'=>$args['base_url'] . '/' . $type_permalink . '/' . urlencode($rc['tag']['permalink']));

            //
            // Get the items for the specified category
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'posts');
            $rc = ciniki_blog_web_posts($ciniki, $settings, $tnid, array('tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink,
                'offset'=>(($page_post_cur-1)*$page_post_limit), 'limit'=>$page_post_limit+1), $args['blogtype']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $posts = $rc['posts'];
            $total_num_posts = $rc['total_num_posts'];

//          $page['title'] .= ($page['title']!=''?' - ':'') . $tag_name;
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
            if( $display_format == 'tradingcards' ) {
                $page['blocks'][] = array('type'=>'tradingcards', 
                    'image_version'=>((isset($settings['page-blog-thumbnail-format'])&&$settings['page-blog-thumbnail-format']=='square-padded')?'original':'thumbnail'),
                    'image_width'=>'600',
                    'more_button_text'=>(isset($settings['page-blog-more-button-text'])?$settings['page-blog-more-button-text']:''),
                    'base_url'=>$args['base_url'], 'noimage'=>'yes', 
                    'limit'=>($display != 'archive' ? $page_post_limit : 0), 
                    'cards'=>$posts,
                    'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);

            } else {
                $page['blocks'][] = array('type'=>'imagelist', 
                    'image_version'=>((isset($settings['page-blog-thumbnail-format'])&&$settings['page-blog-thumbnail-format']=='square-padded')?'original':'thumbnail'),
                    'image_width'=>'600',
                    'more_button_text'=>(isset($settings['page-blog-more-button-text'])?$settings['page-blog-more-button-text']:''),
                    'base_url'=>$args['base_url'], 'noimage'=>'yes', 
                    'limit'=>($display != 'archive' ? $page_post_limit : 0), 
                    'list'=>$posts,
                    'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
            }
            
            //
            // check if pagination is required
            //
            if( $display != 'archive' && $total_num_posts > $page_post_limit ) {
                $page['blocks'][] = array('type'=>'multipagenav', 'cur_page'=>$page_post_cur, 'total_pages'=>ceil($total_num_posts/$page_post_limit),
                    'base_url'=>$base_url);
            }
        } else {
            $page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but there doesn't seem to be any posts available.");
        }
    }

    elseif( $display == 'archive' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'archive');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');

        $rc = ciniki_blog_web_archive($ciniki, $settings, $ciniki['request']['tnid'], $args['blogtype']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        if( $args['module_page'] != 'ciniki.blog.archive' ) {
            $page['breadcrumbs'][] = array('name'=>'Archive', 'url'=>$base_url);
            $base_url = $args['base_url'] . '/archive';
            $page['title'] = 'Archive';
        } else {
            $base_url = $args['base_url'];
        }

        if( count($rc['archive']) > 0 ) {
            $page['blocks'][] = array('type'=>'archivelist', 
                'base_url'=>$base_url,
                'archive'=>$rc['archive']);
        } else {
            $page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but there doesn't seem to be any posts archived.");
        }
    }

    elseif( $display == 'type' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'tags');
        $rc = ciniki_blog_web_tags($ciniki, $settings, $ciniki['request']['tnid'], $tag_type, $args['blogtype']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['types'][$tag_type]['tags']) ) {
            $tags = $rc['types'][$tag_type]['tags'];
        } else {
            $tags = array();
        }
    
//      $page['title'] .= ($page['title']!=''?' - ':'') . $tag_types[$type_permalink]['name'];
        $page['title'] = $tag_types[$type_permalink]['name'];
        $page['breadcrumbs'][] = array('name'=>$tag_types[$type_permalink]['name'], 'url'=>$args['base_url'] . '/' . $type_permalink);

        if( count($tags) > 25 || $tag_type == '20' ) {
            $page['blocks'][] = array('type'=>'tagcloud', 'base_url'=>$base_url, 'tags'=>$tags);
        } elseif( count($tags) > 0 ) {
            if( isset($settings['page-blog-categories-format']) && $settings['page-blog-categories-format'] == 'tagimagelist' ) {
                $page['blocks'][] = array('type'=>'tagimagelist', 'base_url'=>$base_url, 'tags'=>$tags,
                //    'image_version'=>((isset($settings['page-blog-list-image-version'])&&$settings['page-blog-list-image-version']=='original')?'original':'thumbnail'),
                    'image_version'=>((isset($settings['page-blog-thumbnail-format'])&&$settings['page-blog-thumbnail-format']=='square-padded')?'original':'thumbnail'),
                    'image_width'=>'400',
                    'noimage'=>'yes',
                    'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
            } else {
                $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url, 'tags'=>$tags,
                    'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
            }
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
            $rc = ciniki_blog_web_tagDetails($ciniki, $settings, $tnid, array('tag_type'=>$tag_type, 'tag_permalink'=>$tag_permalink), $args['blogtype']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['tag']['tag_name']) ) {
                $tag_name = $rc['tag']['tag_name'];
                $page['breadcrumbs'][] = array('name'=>$tag_types[$type_permalink]['name'], 'url'=>$args['base_url'] . '/' . $type_permalink);
                $page['breadcrumbs'][] = array('name'=>$tag_name, 'url'=>$args['base_url'] . '/' . $type_permalink . '/' . urlencode($rc['tag']['permalink']));
            }
        }

        //
        // Load the post to get all the details, and the list of images.
        // It's one query, and we can find the requested image, and figure out next
        // and prev from the list of images returned
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'postDetails');
        $rc = ciniki_blog_web_postDetails($ciniki, $settings, $ciniki['request']['tnid'], array('permalink'=>$post_permalink, 'blogtype'=>$args['blogtype']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $post = $rc['post'];

        if( isset($post['image_id']) && $post['image_id'] > 0 ) {
            // Check for the primary image in the post
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
            $rc = ciniki_web_getScaledImageURL($ciniki, $post['image_id'], 'original', '500', 0);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $ciniki['response']['head']['og']['image'] = $rc['domain_url'];
        }

        if( isset($post['synopsis']) && $post['synopsis'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($post['synopsis']);
        } elseif( isset($post['content']) && $post['content'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($post['content']);
        }

//      $page['title'] .= ($page['title']!=''?' - ':'') . $post['title'];
        $page['title'] = $post['title'];
        if( isset($post['subtitle']) ) {
            $page['subtitle'] = $post['subtitle'];
        }
        if( isset($settings['page-blog-post-header-share-buttons']) ) {
            $page['article_header_share_buttons'] = $settings['page-blog-post-header-share-buttons'];
        }
        $page['meta'] = array();
        $page['meta']['date'] = $post['publish_date'];
        if( isset($post['categories']) ) {
            $page['meta']['categories'] = $post['categories'];
            $page['meta']['divider'] = (isset($settings['page-blog-meta-divider'])?$settings['page-blog-meta-divider']:' | ');
            $page['meta']['category_base_url'] = $args['base_url'] . '/category';
            $page['meta']['category_prefix'] = (isset($settings['page-blog-meta-category-prefix'])?$settings['page-blog-meta-category-prefix']:'');
            $page['meta']['categories_prefix'] = (isset($settings['page-blog-meta-categories-prefix'])?$settings['page-blog-meta-categories-prefix']:'');
        }

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
                    $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$post['image_id'], 'caption'=>$post['image_caption'], 'title'=>'');
                } else {
                    $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$post['image_id'], 'caption'=>$post['image_caption'], 'title'=>$post['title']);
                }
            }
            if( isset($post['content']) && $post['content'] != '' ) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$post['content']);
            }
            if( isset($post['files']) && count($post['files']) > 0 ) {
                $page['blocks'][] = array('type'=>'files', 'title'=>'', 'section'=>'files', 'base_url'=>$base_url . '/download', 'files'=>$post['files']);
            }
            if( isset($post['audio']) && count($post['audio']) > 0 ) {
                $page['blocks'][] = array('type'=>'audiolist', 'section'=>'audio', 'audio'=>$post['audio'], 'titles'=>'yes');
            }
            if( isset($post['links']) && count($post['links']) > 0 ) {
                $page['blocks'][] = array('type'=>'links', 'section'=>'links', 'title'=>'', 'links'=>$post['links']);
            }
            if( isset($post['categories']) || isset($post['tags']) ) {
                $meta_footer = array();
                if( isset($post['categories']) ) {
                    $meta_footer['categories'] = $post['categories'];
                    $meta_footer['category_divider'] = (isset($settings['page-blog-meta-divider'])?$settings['page-blog-meta-divider']:' | ');
                    $meta_footer['category_base_url'] = $args['base_url'] . '/category';
                    $meta_footer['category_prefix'] = (isset($settings['page-blog-meta-category-prefix'])?$settings['page-blog-meta-category-prefix']:'');
                    $meta_footer['categories_prefix'] = (isset($settings['page-blog-meta-categories-prefix'])?$settings['page-blog-meta-categories-prefix']:'');
                }
                if( isset($post['tags']) ) {
                    $meta_footer['tags'] = $post['tags'];
                    $meta_footer['tag_divider'] = (isset($settings['page-blog-meta-divider'])?$settings['page-blog-meta-divider']:' | ');
                    $meta_footer['tag_base_url'] = $args['base_url'] . '/tag';
                    $meta_footer['tag_prefix'] = (isset($settings['page-blog-meta-tag-prefix'])?$settings['page-blog-meta-tag-prefix']:'');
                    $meta_footer['tags_prefix'] = (isset($settings['page-blog-meta-tags-prefix'])?$settings['page-blog-meta-tags-prefix']:'');
                }
                $page['blocks'][] = array('type'=>'meta', 'section'=>'meta', 'title'=>'', 'meta'=>$meta_footer);
            }
            if( $args['blogtype'] == 'blog' && (!isset($settings['page-blog-share-buttons']) || $settings['page-blog-share-buttons'] == 'yes') ) {
                $tags = array();
                $page['blocks'][] = array('type'=>'sharebuttons', 'section'=>'share', 'pagetitle'=>$post['title'], 'tags'=>$tags);
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
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.blog.50', 'msg'=>"We're sorry, the page you requested does not exist."));
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
    elseif( isset($settings['page-blog-submenu']) && $settings['page-blog-submenu'] == 'categories' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'tags');
        $rc = ciniki_blog_web_tags($ciniki, $settings, $tnid, 10, $args['blogtype']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.56', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['types'][10]['tags']) ) {
            $page['submenu'] = array();
            foreach($rc['types'][10]['tags'] as $tag) {
                $page['submenu'][] = array('name'=>$tag['name'], 'url'=>$args['base_url'] . '/category/' . $tag['permalink']);
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
        $rc = ciniki_blog_web_posts($ciniki, $settings, $tnid, array('latest'=>'yes', 'limit'=>3), $args['blogtype']);
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
                //'image_version'=>((isset($settings['page-blog-list-image-version'])&&$settings['page-blog-list-image-version']=='original')?'original':'thumbnail'),
                'image_version'=>((isset($settings['page-blog-thumbnail-format'])&&$settings['page-blog-thumbnail-format']=='square-padded')?'original':'thumbnail'),
                'image_width'=>'400',
                'more_button_text'=>(isset($settings['page-blog-more-button-text'])?$settings['page-blog-more-button-text']:''),
                'base_url'=>$args['base_url'], 'noimage'=>'yes', 'list'=>$posts,
                'thumbnail_format'=>$thumbnail_format, 'thumbnail_padding_color'=>$thumbnail_padding_color);
        }

        //
        // Get the list of tags
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'tags');
        $rc = ciniki_blog_web_tags($ciniki, $settings, $ciniki['request']['tnid'], 0, $args['blogtype']);
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
