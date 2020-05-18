//
// The blog app to manage the blog for the tenant
//
function ciniki_blog_postfiles() {
    this.init = function() {
        //
        // The panel to display the add form
        //
        this.add = new M.panel('Add File',
            'ciniki_blog_postfiles', 'add',
            'mc', 'medium', 'sectioned', 'ciniki.blog.postfiles.edit');
        this.add.default_data = {'type':'20'};
        this.add.data = {}; 
        this.add.sections = {
            '_file':{'label':'File', 'fields':{
                'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
            }},
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_blog_postfiles.addFile();'},
            }},
        };
        this.add.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.add.addButton('save', 'Save', 'M.ciniki_blog_postfiles.addFile();');
        this.add.addClose('Cancel');

        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('File',
            'ciniki_blog_postfiles', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.blog.info.edit');
        this.edit.file_id = 0;
        this.edit.data = null;
        this.edit.sections = {
            'info':{'label':'Details', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_blog_postfiles.saveFile();'},
                'download':{'label':'Download', 'fn':'M.ciniki_blog_postfiles.downloadFile(M.ciniki_blog_postfiles.edit.file_id);'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_blog_postfiles.deleteFile();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            return this.data[i]; 
        }
        this.edit.sectionData = function(s) {
            return this.data[s];
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.blog.postFileHistory', 'args':{'tnid':M.curTenantID, 
                'file_id':this.file_id, 'field':i}};
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_blog_postfiles.saveFile();');
        this.edit.addClose('Cancel');
    }

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_blog_postfiles', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.file_id != null && args.file_id > 0 ) {
            this.showEdit(cb, args.file_id);
        } else if( args.post_id != null && args.post_id > 0 && args.add != null && args.add == 'yes' ) {
            this.showAdd(cb, args.post_id);
        } else {
            M.alert('Invalid request');
        }
    }

    this.showMenu = function(cb) {
        this.menu.refresh();
        this.menu.show(cb);
    };

    this.showAdd = function(cb, pid) {
        this.add.reset();
        this.add.data = {'name':''};
        this.add.file_id = 0;
        this.add.post_id = pid;
        this.add.refresh();
        this.add.show(cb);
    };

    this.addFile = function() {
        var c = this.add.serializeFormData('yes');

        if( c != '' ) {
            M.api.postJSONFormData('ciniki.blog.postFileAdd', 
                {'tnid':M.curTenantID, 'post_id':M.ciniki_blog_postfiles.add.post_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        var p = M.ciniki_blog_postfiles.add;
                        p.file_id = rsp.id;
                        p.close();
                    });
        } else {
            M.ciniki_blog_postfiles.add.close();
        }
    };

    this.showEdit = function(cb, fid) {
        if( fid != null ) { this.edit.file_id = fid; }
        M.api.getJSONCb('ciniki.blog.postFileGet', {'tnid':M.curTenantID, 
            'file_id':this.edit.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_blog_postfiles.edit;
                p.data = rsp.file;
                p.refresh();
                p.show(cb);
            });
    };

    this.saveFile = function() {
        var c = this.edit.serializeFormData('no');
        if( c != '' ) {
            M.api.postJSONFormData('ciniki.blog.postFileUpdate', 
                {'tnid':M.curTenantID, 'file_id':this.edit.file_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_blog_postfiles.edit.close();
                });
        }
    };

    this.deleteFile = function() {
        M.confirm('Are you sure you want to delete \'' + this.edit.data.name + '\'?  All information about it will be removed and unrecoverable.',null,function() {
            M.api.getJSONCb('ciniki.blog.postFileDelete', {'tnid':M.curTenantID, 
                'file_id':M.ciniki_blog_postfiles.edit.file_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_blog_postfiles.edit.close();
                });
        });
    };

    this.downloadFile = function(fid) {
        M.api.openFile('ciniki.blog.postFileDownload', {'tnid':M.curTenantID, 'file_id':fid});
    };
}
