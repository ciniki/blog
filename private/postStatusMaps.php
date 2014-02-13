<?php
//
// Description
// -----------
// This function returns the array of status text for ciniki_blog_posts.status.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_blog_postStatusMaps($ciniki) {
	
	$status_maps = array(
		'10'=>'Draft',
		'40'=>'Published',
		'60'=>'Removed',
		);
	
	return array('stat'=>'ok', 'maps'=>$status_maps);
}
?>
