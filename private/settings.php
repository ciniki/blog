<?php
//
// Description
// -----------
// This function returns the settings for the blog module for a busines.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get the settings for.
// 
// Returns
// -------
//
function ciniki_blog_settings($ciniki, $tnid) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    return ciniki_core_dbDetailsQuery($ciniki, 'ciniki_blog_settings', 'tnid', $tnid, 'ciniki.blog', 'settings', '');
}
