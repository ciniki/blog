//
function ciniki_blog_postproducts() {
    //
    // Panels
    //
    this.init = function() {
        //
        // The panel to edit an existing reference
        //
        this.edit = new M.panel('Product',
            'ciniki_blog_postproducts', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.blog.postproducts.edit');
        this.edit.data = {};
        this.edit.sections = {
            'product':{'label':'Product', 'fields':{
                'object_id':{'label':'', 'hidelabel':'yes', 'hint':'Search for product', 'type':'fkid', 'livesearch':'yes'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save Product', 'fn':'M.ciniki_blog_postproducts.saveRef();'},
                'delete':{'label':'Delete Product', 'fn':'M.ciniki_blog_postproducts.deleteRef();'},
                }},
            };
        this.edit.fieldValue = function(s, i, d) { 
            if( i == 'object_id_fkidstr' ) { return this.data.object_name; }
            if( this.data[i] == null ) { return ''; }
            return this.data[i]; 
        };
        this.edit.liveSearchCb = function(s, i, value) {
            if( i == 'object_id' ) {
                var rsp = M.api.getJSONBgCb('ciniki.products.productSearch',
                    {'tnid':M.curTenantID, 'start_needle':value, 'limit':25},
                    function(rsp) {
                        M.ciniki_blog_postproducts.edit.liveSearchShow(s, i, M.gE(M.ciniki_blog_postproducts.edit.panelUID + '_' + i), rsp.products);
                    });
            }
        };
        this.edit.liveSearchResultValue = function(s, f, i, j, d) {
            if( f == 'object_id' ) { return d.product.name; }
            return '';
        };
        this.edit.liveSearchResultRowFn = function(s, f, i, j, d) {
            if( f == 'object_id' ) {
                return 'M.ciniki_blog_postproducts.edit.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.product.name) + '\',\'' + d.product.id + '\');';
            }
        };
        this.edit.updateField = function(s, fid, oname, oid) {
            M.gE(this.panelUID + '_' + fid).value = oid;
            M.gE(this.panelUID + '_' + fid + '_fkidstr').value = unescape(oname);
            this.removeLiveSearch(s, fid);
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.blog.postRefHistory', 'args':{'tnid':M.curTenantID, 
                'object_id':this.object_id, 'field':i}};
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_blog_postproducts.saveRef();');
        this.edit.addClose('cancel');
    };

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_blog_postproducts', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.ref_id != null && args.ref_id > 0 ) {
            // Edit an existing reference
            this.showEdit(cb, 0, args.ref_id);
        } else if( args.post_id != null && args.post_id > 0 ) {
            // Add a new reference for a post
            this.showEdit(cb, args.post_id, 0);
        }
    };

    this.showEdit = function(cb, pid, rid) {
        if( pid != null ) { this.edit.post_id = pid; }
        if( rid != null ) { this.edit.ref_id = rid; }
        if( this.edit.ref_id > 0 ) {
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.blog.postRefGet', 
                {'tnid':M.curTenantID, 'ref_id':this.edit.ref_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_blog_postproducts.edit;
                    p.data = rsp.ref;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.data = {};
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveRef = function() {
        if( this.edit.ref_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.blog.postRefUpdate', 
                    {'tnid':M.curTenantID, 'ref_id':this.edit.ref_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_blog_postproducts.edit.close();
                    });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.blog.postRefAdd', 
                    {'tnid':M.curTenantID, 'post_id':this.edit.post_id, 
                    'object':'ciniki.products.product'}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_blog_postproducts.edit.close();
                    });
            } else {
                this.edit.close();
            }
        }
    };

    this.deleteRef = function() {
        M.confirm("Are you sure you want to remove this product?",null,function() {
            M.api.getJSONCb('ciniki.blog.postRefDelete', 
                {'tnid':M.curTenantID, 'ref_id':M.ciniki_blog_postproducts.edit.ref_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_blog_postproducts.edit.close();
                });
        });
    };
}
