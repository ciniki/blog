<?php
//
// Description
// -----------
// This function returns the index details for an object
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get blog for.
//
// Returns
// -------
//
function ciniki_blog_hooks_webIndexObject($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.52', 'msg'=>'No object specified'));
    }

    if( !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.53', 'msg'=>'No object ID specified'));
    }

    //
    // Setup the base_url for use in index
    //
    if( isset($args['base_url']) ) {
        $base_url = $args['base_url'];
    } else {
        $base_url = '/blog';
    }

    if( $args['object'] == 'ciniki.blog.post' ) {
        //
        // Get the category for the artist
        //
/*        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.blog', 0x02) ) {
            $strsql = "SELECT tag_type, permalink "
                . "FROM ciniki_blog_post_tags "
                . "WHERE post_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND tag_type = 10 "
                . "LIMIT 1 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'item');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['item']['permalink']) ) {
                $category_permalink = $rc['item']['permalink'];
            }
        } */

        $strsql = "SELECT id, title, subtitle, permalink, status, publish_to, "
            . "primary_image_id, excerpt, content "
            . "FROM ciniki_blog_posts "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.blog', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.54', 'msg'=>'Object not found'));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.blog.55', 'msg'=>'Object not found'));
        }

        //
        // Check if item is visible on website
        //
        if( $rc['item']['status'] != 40 ) {
            return array('stat'=>'ok');
        }
        if( ($rc['item']['publish_to']&0x01) != 0x01 ) {
            return array('stat'=>'ok');
        }
        $object = array(
            'label'=>'Blog',
            'title'=>$rc['item']['title'],
            'subtitle'=>$rc['item']['subtitle'],
            'meta'=>'',
            'primary_image_id'=>$rc['item']['primary_image_id'],
            'synopsis'=>$rc['item']['excerpt'],
            'object'=>'ciniki.blog.post',
            'object_id'=>$rc['item']['id'],
            'primary_words'=>$rc['item']['title'],
            'secondary_words'=>$rc['item']['subtitle'] . $rc['item']['excerpt'],
            'tertiary_words'=>$rc['item']['content'],
            'weight'=>20000,
            'url'=>$base_url 
//                . (isset($category_permalink) ? '/category/' . $category_permalink : '')
                . '/' . $rc['item']['permalink']
            );
        return array('stat'=>'ok', 'object'=>$object);
    }

    return array('stat'=>'ok');
}
?>
