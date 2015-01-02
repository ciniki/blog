<?php
//
// Description
// -----------
// This function returns a blog post formatted for use as an email message
// with textmsg and htmlmsg setup, along with images prepared on the website.
//
// Arguments
// ---------
// ciniki:
// business_id:			The business ID to check the session user against.
// method:				The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_blog_hooks_emailGet($ciniki, $business_id, $args) {
	
	if( isset($args['object']) && $args['object'] != ''
		&& isset($args['object_id']) && $args['object_id'] > 0 
		&& isset($ciniki['business']['modules']['ciniki.blog']['flags'])
		&& ($ciniki['business']['modules']['ciniki.blog']['flags']&0x7000) > 0		// Blog subscriptions enabled
		) {
		$email = array('subject'=>'', 'text_content'=>'', 'html_content'=>'');
	
		//
		// Setup fake web request
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'createFakeRequest');
		$rc = ciniki_web_createFakeRequest($ciniki, $business_id);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$web_ciniki = $rc['web_ciniki'];
		$settings = $rc['settings'];
		
		//
		// Get the blog post from the database
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'postDetails');
		$rc = ciniki_blog_web_postDetails($web_ciniki, $settings, $business_id, 
			array('id'=>$args['object_id'], 'blogtype'=>'blog'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['post']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2122', 'msg'=>'Unable to find blog post'));
		}
		$post = $rc['post'];

		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processBlogPost');
		$rc = ciniki_web_processBlogPost($web_ciniki, $settings, $post, 
			array('output'=>'email', 'blogtype'=>'blog'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$email['subject'] = $post['title'];
		$email['html_content'] = $rc['content'];
		$email['text_content'] = $rc['text_content'];

		//
		// Add followup text to link back to website for full blog post
		//
		$email['html_content'] .= "<p>"
			. "<a href='" . $web_ciniki['request']['domain_base_url'] . "/blog/" . $post['permalink'] . "'>View Online</a>"
			. "</p>";

		$email['text_content'] .= "\n\nView online at: " . $web_ciniki['request']['domain_base_url'] . "/blog/" . $post['permalink'] . "\n";

		return array('stat'=>'ok', 'email'=>$email);
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2123', 'msg'=>'Internal Error'));
}
?>
