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
		// 0x01
		array('flag'=>array('bit'=>'1', 'name'=>'Public Blog')),
		array('flag'=>array('bit'=>'2', 'name'=>'Public Categories')),
		array('flag'=>array('bit'=>'3', 'name'=>'Public Tags')),
//		array('flag'=>array('bit'=>'4', 'name'=>'')),
		// 0x10
//		array('flag'=>array('bit'=>'5', 'name'=>'Customer Blog')),
//		array('flag'=>array('bit'=>'6', 'name'=>'Customer Categories')),
//		array('flag'=>array('bit'=>'7', 'name'=>'Customer Tags')),
//		array('flag'=>array('bit'=>'8', 'name'=>'')),
		// 0x0100
		array('flag'=>array('bit'=>'9', 'name'=>'Member Blog')),
		array('flag'=>array('bit'=>'10', 'name'=>'Member Categories')),
		array('flag'=>array('bit'=>'11', 'name'=>'Member Tags')),
//		array('flag'=>array('bit'=>'12', 'name'=>'')),
		// 0x1000
		array('flag'=>array('bit'=>'13', 'name'=>'Public Blog Subscriptions')),
//		array('flag'=>array('bit'=>'14', 'name'=>'Customer Blog Subscriptions')),
//		array('flag'=>array('bit'=>'15', 'name'=>'Member Blog Subscriptions')),
//		array('flag'=>array('bit'=>'16', 'name'=>'')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>
