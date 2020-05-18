//
function ciniki_blog_postrecipes() {
    //
    // Panels
    //
    this.init = function() {
        //
        // The panel to edit an existing reference
        //
        this.edit = new M.panel('Recipe',
            'ciniki_blog_postrecipes', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.blog.recipes.edit');
        this.edit.data = {};
        this.edit.sections = {
            'recipe':{'label':'Recommended Recipe', 'fields':{
                'object_id':{'label':'', 'hidelabel':'yes', 'hint':'Search for recipe', 'type':'fkid', 'livesearch':'yes'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save Recipe', 'fn':'M.ciniki_blog_postrecipes.saveRef();'},
                'delete':{'label':'Delete Recipe', 'fn':'M.ciniki_blog_postrecipes.deleteRef();'},
                }},
            };
        this.edit.fieldValue = function(s, i, d) { 
            if( i == 'object_id_fkidstr' ) { return this.data.object_name; }
            if( this.data[i] == null ) { return ''; }
            return this.data[i]; 
        };
        this.edit.liveSearchCb = function(s, i, value) {
            if( i == 'object_id' ) {
                var rsp = M.api.getJSONBgCb('ciniki.recipes.recipeSearch',
                    {'tnid':M.curTenantID, 'start_needle':value, 'limit':25},
                    function(rsp) {
                        M.ciniki_blog_postrecipes.edit.liveSearchShow(s, i, M.gE(M.ciniki_blog_postrecipes.edit.panelUID + '_' + i), rsp.recipes);
                    });
            }
        };
        this.edit.liveSearchResultValue = function(s, f, i, j, d) {
            if( f == 'object_id' ) { return d.recipe.name; }
            return '';
        };
        this.edit.liveSearchResultRowFn = function(s, f, i, j, d) {
            if( f == 'object_id' ) {
                return 'M.ciniki_blog_postrecipes.edit.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.recipe.name) + '\',\'' + d.recipe.id + '\');';
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
        this.edit.addButton('save', 'Save', 'M.ciniki_blog_postrecipes.saveRef();');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_blog_postrecipes', 'yes');
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
                    var p = M.ciniki_blog_postrecipes.edit;
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
                        M.ciniki_blog_postrecipes.edit.close();
                    });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.blog.postRefAdd', 
                    {'tnid':M.curTenantID, 'post_id':this.edit.post_id, 
                    'object':'ciniki.recipes.recipe'}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_blog_postrecipes.edit.close();
                    });
            } else {
                this.edit.close();
            }
        }
    };

    this.deleteRef = function() {
        if( confirm("Are you sure you want to remove this recommended recipe?") ) {
            var rsp = M.api.getJSONCb('ciniki.blog.postRefDelete', 
                {'tnid':M.curTenantID, 'ref_id':this.edit.ref_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_blog_postrecipes.edit.close();
                });
        }   
    };
}
