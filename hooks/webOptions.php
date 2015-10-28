<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get events for.
//
// args:			The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_blog_hooks_webOptions(&$ciniki, $business_id, $args) {

	//
	// Check to make sure the module is enabled
	//
	if( !isset($ciniki['business']['modules']['ciniki.blog']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2555', 'msg'=>"I'm sorry, the page you requested does not exist."));
	}

	//
	// Get the settings from the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'business_id', $business_id, 'ciniki.web', 'settings', 'page-blog');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['settings']) ) {
		$settings = array();
	} else {
		$settings = $rc['settings'];
	}


	$options = array();
	$options[] = array('option'=>array(
		'label'=>'Submenu',
		'setting'=>'page-blog-submenu', 
		'type'=>'toggle',
		'value'=>(isset($settings['page-blog-submenu'])?$settings['page-blog-submenu']:'yes'),
		'toggles'=>array(
			array('toggle'=>array('value'=>'no', 'label'=>'No')),
			array('toggle'=>array('value'=>'yes', 'label'=>'Yes')),
			),
		));

	$options[] = array('option'=>array(
		'label'=>'Sidebar',
		'setting'=>'page-blog-sidebar', 
		'type'=>'toggle',
		'value'=>(isset($settings['page-blog-sidebar'])?$settings['page-blog-sidebar']:'no'),
		'toggles'=>array(
			array('toggle'=>array('value'=>'no', 'label'=>'No')),
			array('toggle'=>array('value'=>'yes', 'label'=>'Yes')),
			),
		));

	$options[] = array('option'=>array(
		'label'=>'Image Size',
		'setting'=>'page-blog-list-image-version', 
		'type'=>'toggle',
		'value'=>(isset($settings['page-blog-list-image-version'])?$settings['page-blog-list-image-version']:'thumbnail'),
		'toggles'=>array(
			array('toggle'=>array('value'=>'thumbnail', 'label'=>'Square')),
			array('toggle'=>array('value'=>'original', 'label'=>'Original')),
			),
		));

	$options[] = array('option'=>array(
		'label'=>'More Button',
		'setting'=>'page-blog-more-button-text', 
		'type'=>'text',
		'value'=>(isset($settings['page-blog-more-button-text'])?$settings['page-blog-more-button-text']:''),
		'hint'=>'... more',
		));

	if( ($ciniki['business']['modules']['ciniki.blog']['flags']&0x06) > 0 ) {
		$options[] = array('option'=>array(
			'label'=>'Meta Divider',
			'setting'=>'page-blog-meta-divider', 
			'type'=>'text',
			'value'=>(isset($settings['page-blog-meta-divider'])?$settings['page-blog-meta-divider']:''),
			'hint'=>' | ',
			));
	}

	if( ($ciniki['business']['modules']['ciniki.blog']['flags']&0x02) > 0 ) {
		$options[] = array('option'=>array(
			'label'=>'Meta Category Prefix',
			'setting'=>'page-blog-meta-category-prefix', 
			'type'=>'text',
			'value'=>(isset($settings['page-blog-meta-category-prefix'])?$settings['page-blog-meta-category-prefix']:''),
			'hint'=>'',
			));
		$options[] = array('option'=>array(
			'label'=>'Meta Categories Prefix',
			'setting'=>'page-blog-meta-categories-prefix', 
			'type'=>'text',
			'value'=>(isset($settings['page-blog-meta-categories-prefix'])?$settings['page-blog-meta-categories-prefix']:''),
			'hint'=>'',
			));
	}

	return array('stat'=>'ok', 'options'=>$options);
}
?>
