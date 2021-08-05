<?php
//
// Description
// -----------
// This function will return the blocks for the website.
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_blog_wng_process(&$ciniki, $tnid, &$request, $section) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.blog']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.86', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.87', 'msg'=>"No section specified."));
    }

    if( $section['ref'] == 'ciniki.blog.latest' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'wng', 'latestProcess');
        return ciniki_blog_wng_latestProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
