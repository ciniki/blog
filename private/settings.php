<?php
//
// Description
// -----------
// This function returns the settings for the blog module for a busines.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get the settings for.
// 
// Returns
// -------
//
function ciniki_blog_settings($ciniki, $business_id) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    return ciniki_core_dbDetailsQuery($ciniki, 'ciniki_blog_settings', 'business_id', $business_id, 'ciniki.blog', 'settings', '');
}
