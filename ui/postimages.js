//
// The app to add/edit blog product images
//
function ciniki_blog_postimages() {
    this.init = function() {
        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Image',
            'ciniki_blog_postimages', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.blog.postimages.edit');
        this.edit.default_data = {};
        this.edit.data = {};
        this.edit.post_id = 0;
        this.edit.post_image_id = 0;
        this.edit.sections = {
            '_image':{'label':'Photo', 'type':'imageform', 'fields':{
                'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
                'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            }},
            '_description':{'label':'Description', 'type':'simpleform', 'fields':{
                'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_blog_postimages.saveImage();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_blog_postimages.deleteImage();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.blog.postImageHistory', 'args':{'tnid':M.curTenantID, 
                'post_image_id':this.post_image_id, 'field':i}};
        };
        this.edit.addDropImage = function(iid) {
            M.ciniki_blog_postimages.edit.setFieldValue('image_id', iid, null, null);
            return true;
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_blog_postimages.saveImage();');
        this.edit.addClose('Cancel');
    };

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_blog_postimages', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.post_id);
        } else if( args.post_image_id != null && args.post_image_id > 0 ) {
            this.showEdit(cb, args.post_image_id);
        }
        return false;
    }

    this.showEdit = function(cb, iid, pid) {
        if( iid != null ) { this.edit.post_image_id = iid; }
        if( pid != null ) { this.edit.post_id = pid; }
        if( this.edit.post_image_id > 0 ) {
            M.api.getJSONCb('ciniki.blog.postImageGet', 
                {'tnid':M.curTenantID, 'post_image_id':this.edit.post_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_blog_postimages.edit;
                    p.data = rsp.image;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.data = {};
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveImage = function() {
        if( this.edit.post_image_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.blog.postImageUpdate', 
                    {'tnid':M.curTenantID, 
                    'post_image_id':this.edit.post_image_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } else {
                                M.ciniki_blog_postimages.edit.close();
                            }
                        });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeFormData('yes');
            M.api.postJSONFormData('ciniki.blog.postImageAdd', 
                {'tnid':M.curTenantID, 'post_id':this.edit.post_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_blog_postimages.edit.close();
                        }
                    });
        }
    };

    this.deleteImage = function() {
        if( confirm('Are you sure you want to delete this image?') ) {
            var rsp = M.api.getJSONCb('ciniki.blog.postImageDelete', {'tnid':M.curTenantID, 
                'post_image_id':this.edit.post_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_blog_postimages.edit.close();
                });
        }
    };
}
