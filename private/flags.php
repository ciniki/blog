<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_blog_flags($ciniki, $modules) {
	$flags = array(
		array('flag'=>array('bit'=>'1', 'name'=>'Public Blog')),
		array('flag'=>array('bit'=>'2', 'name'=>'Public Categories')),
		array('flag'=>array('bit'=>'3', 'name'=>'Public Tags')),
//		array('flag'=>array('bit'=>'5', 'name'=>'Customer Blog')),
//		array('flag'=>array('bit'=>'6', 'name'=>'Customer Categories')),
//		array('flag'=>array('bit'=>'7', 'name'=>'Customer Tags')),
		array('flag'=>array('bit'=>'9', 'name'=>'Member Blog')),
		array('flag'=>array('bit'=>'10', 'name'=>'Member Categories')),
		array('flag'=>array('bit'=>'11', 'name'=>'Member Tags')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>
