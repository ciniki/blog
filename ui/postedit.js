//
// This is the main UI for a blog post
//
function ciniki_blog_postedit() {
    this.statusOptions = {
        '10':'Draft',
        '40':'Published',
        '60':'Removed',
        };
    this.subscriptionOptions = {
        'no':'No',
        'yes':'Yes',
        };
    this.publishtoFlags = {
        '1':{'name':'Public'},
//      '2':{'name':'Customers'},
        '3':{'name':'Members'},
        };

    //
    // The edit panel
    //
    this.edit = new M.panel('Blog Post',
        'ciniki_blog_postedit', 'edit',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.blog.postedit.edit');
    this.edit.data = {};
    this.edit.post_id = 0;
    this.edit.blogtype = 'blog';
//      this.edit.formtab = 'generic';
//      this.edit.formtabs = {'label':'', 'field':'type', 'tabs':{
//          'generic':{'label':'Generic', 'field_id':1, 'form':'generic'},
//          'winekit':{'label':'Wine Kit', 'field_id':64, 'form':'winekit'},
//          }};
    this.edit.forms = {};
    this.edit.forms.generic = {
        '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 
                'controls':'all', 'history':'no'},
            }},
        '_image_caption':{'label':'', 'aside':'yes', 'fields':{
            'primary_image_caption':{'label':'Caption', 'type':'text'},
            }},
        'info':{'label':'', 'aside':'yes', 'fields':{
            'title':{'label':'Title', 'hint':'', 'required':'yes', 'type':'text'},
            'subtitle':{'label':'Subtitle', 'hint':'', 'type':'text'},
            'publish_date':{'label':'Date', 'type':'date', 'size':'medium'},
            'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':this.statusOptions},
            'publish_to':{'label':'Publish To', 'active':'no', 'type':'flags', 'none':'no', 'join':'yes', 'flags':this.publishtoFlags},
            }},
        'subscriptions':{'label':'Subscriptions', 'active':'no', 'aside':'yes', 'fields':{
            }},
        '_webcollections':{'label':'Web Collections', 'aside':'yes', 'active':'no', 'fields':{
            'webcollections':{'label':'', 'hidelabel':'yes', 'type':'collection'},
            }},
        '_categories':{'label':'Categories', 'aside':'yes', 'visible':'no', 'fields':{
            'categories':{'label':'', 'hidelabel':'yes', 'active':'no', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
            }},
        '_tags':{'label':'Keywords', 'aside':'yes', 'visible':'no', 'fields':{
            'tags':{'label':'', 'hidelabel':'yes', 'active':'no', 'type':'tags', 'tags':[], 'hint':'Enter a new tag:'},
            }},
        '_excerpt':{'label':'Synopsis', 'fields':{
            'excerpt':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea', 'size':'small'},
            }},
        '_content':{'label':'Post', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_blog_postedit.edit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_blog_postedit.deletePost();'},
            }},
        };
    this.edit.sections = this.edit.forms.generic;
    this.edit.fieldValue = function(s, i, d) {
        if( this.data[i] != null ) { return this.data[i]; }
        return '';
    };
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.blog.postHistory', 'args':{'tnid':M.curTenantID,
            'post_id':this.post_id, 'field':i}};
    }
    this.edit.addDropImage = function(iid) {
        M.ciniki_blog_postedit.edit.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.edit.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.edit.save = function() {
        if( !this.checkForm() ) { return false; }
        if( this.post_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.blog.postUpdate',
                    {'tnid':M.curTenantID, 'post_id':this.post_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_blog_postedit.edit.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            if( this.sections.info.fields.publish_to.active == 'no' ) {
                c += '&publish_to=' + this.data.publish_to;
            }
            M.api.postJSONCb('ciniki.blog.postAdd',
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_blog_postedit.edit.close();
                });
        }
    };
    this.edit.addButton('save', 'Save', 'M.ciniki_blog_postedit.edit.save();');
    this.edit.addClose('Cancel');

    this.start = function(cb, aP, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }
        var aC = M.createContainer(aP, 'ciniki_blog_postedit', 'yes');
        if( aC == null ) {
            M.alert('App Error');
            return false;
        }

        this.edit.forms.generic.info.fields.subtitle.visible = (M.curTenant.modules['ciniki.blog'] != null && (M.curTenant.modules['ciniki.blog'].flags&0x010000))>0?'yes':'no';
        //
        // Check if web collections are enabled
        //
        if( M.curTenant.modules['ciniki.web'] != null 
            && (M.curTenant.modules['ciniki.web'].flags&0x08) ) {
            this.edit.sections._webcollections.active = 'yes';
        } else {
            this.edit.sections._webcollections.active = 'no';
        }


        this.showEdit(cb, args.post_id, args.blogtype);
    }

    this.showEdit = function(cb, pid, blogtype) {
        this.edit.reset();
        if( pid != null ) { this.edit.post_id = pid; }
        if( blogtype != null ) { this.edit.blogtype = blogtype; }
        this.edit.sections._categories.visible=((M.curTenant.modules['ciniki.blog'].flags&0x222)>0)?'yes':'no';
        this.edit.sections._categories.fields.categories.active=((M.curTenant.modules['ciniki.blog'].flags&0x222)>0)?'yes':'no';
        this.edit.sections._tags.visible=((M.curTenant.modules['ciniki.blog'].flags&0x444)>0)?'yes':'no';
        this.edit.sections._tags.fields.tags.active=((M.curTenant.modules['ciniki.blog'].flags&0x444)>0)?'yes':'no';
        this.publishToFlags = {};
        var numBlogs = 0;
        if( (M.curTenant.modules['ciniki.blog'].flags&0x0001) > 0 ) {
            this.publishToFlags['1'] = {'name':'Public'}; 
            numBlogs++; 
        }
        if( (M.curTenant.modules['ciniki.blog'].flags&0x0100) > 0 ) {
            this.publishToFlags['3'] = {'name':'Members'}; 
            numBlogs++; 
        }
        if( numBlogs > 1 && M.curTenant.modules['ciniki.blog'].flags&0x111 > 0 ) {
            this.edit.sections.info.fields.publish_to.active = ((M.curTenant.modules['ciniki.blog'].flags&0x111)>0)?'yes':'no';
            this.edit.sections.info.fields.publish_to.flags = this.publishToFlags;
        } else {
            this.edit.sections.info.fields.publish_to.active = 'no';
        }
        // Must be backwards so default is set to default to Public
        if( this.edit.blogtype == 'memberblog' ) {
            this.edit.data = {'status':'10', 'publish_to':'4'};
        } else if( this.edit.blogtype == 'blog' ) { 
            this.edit.data = {'status':'10', 'publish_to':'1'};
        }
/*      if( this.edit.post_id > 0 ) { */
        //
        // Always request the post, if a new post, the tags, subscriptions and collections will be returned along
        // with defaults for the blog
        //
            M.api.getJSONCb('ciniki.blog.postGet', {'tnid':M.curTenantID,
                'post_id':this.edit.post_id, 'categories':'yes', 'tags':'yes', 
                'webcollections':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_blog_postedit.edit;
                    p.data = rsp.post;
                    p.sections._categories.fields.categories.tags = [];
                    if( (M.curTenant.modules['ciniki.blog'].flags&0x222)>0 && rsp.categories != null ) {
                        for(i in rsp.categories) {
                            p.sections._categories.fields.categories.tags.push(rsp.categories[i].tag.name);
                        }
                    }
                    p.sections.subscriptions.active = 'no';
                    p.sections.subscriptions.fields = {};
                    if( M.curTenant.modules['ciniki.subscriptions'] != null 
                        && M.curTenant.modules['ciniki.mail'] != null 
                        && (M.curTenant.modules['ciniki.blog'].flags&0x7000) > 0  // Blog subscriptions active
                        ) {
                        if( rsp.post.subscriptions != null && rsp.post.subscriptions.length > 0 ) {
                            p.sections.subscriptions.active = 'yes';
                            for(i in rsp.post.subscriptions) {
                                if( rsp.post.subscriptions[i].subscription.mailing_status <= '20' ) {
                                    p.sections.subscriptions.fields['subscription-' + rsp.post.subscriptions[i].subscription.id] = {'label':rsp.post.subscriptions[i].subscription.name,
                                        'type':'toggle', 'default':'no', 'toggles':M.ciniki_blog_postedit.subscriptionOptions};
                                    p.data['subscription-' + rsp.post.subscriptions[i].subscription.id] = rsp.post.subscriptions[i].subscription.status;
                                } else {
                                    p.sections.subscriptions.fields['subscription-' + rsp.post.subscriptions[i].subscription.id] = {'label':rsp.post.subscriptions[i].subscription.name,
                                        'type':'text', 'editable':'no', 'size':'small'};
                                    p.data['subscription-' + rsp.post.subscriptions[i].subscription.id] = rsp.post.subscriptions[i].subscription.mailing_status_text;
                                }
                            }
                        }
                    }
                    p.sections._tags.fields.tags.tags = [];
                    if( (M.curTenant.modules['ciniki.blog'].flags&0x444)>0 && rsp.tags != null ) {
                        for(i in rsp.tags) {
                            p.sections._tags.fields.tags.tags.push(rsp.tags[i].tag.name);
                        }
                    }
                    if( p.post_id == 0 ) {
                        p.data.publish_date = M.dateFormat(new Date());
                        p.data.status = 10;
                        if( p.blogtype == 'memberblog' ) {
                            p.data.publish_to = 4;
                        } else if( p.blogtype == 'blog' ) { 
                            p.data.publish_to = 1;
                        }
                        p.sections._buttons.buttons.delete.visible = 'no';
                    } else {
                        p.sections._buttons.buttons.delete.visible = 'yes';
                    }
                    p.refresh();
                    p.show(cb);
                });
/*      } else {
            this.edit.post_id = 0;
            if( (M.curTenant.modules['ciniki.blog'].flags&0x0666)>0 ) {
                M.api.getJSONCb('ciniki.blog.postTags', {'tnid':M.curTenantID}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_blog_postedit.edit;
                    p.sections._categories.fields.categories.tags = [];
                    if( rsp.categories != null ) {
                        for(i in rsp.categories) {
                            p.sections._categories.fields.categories.tags.push(rsp.categories[i].tag.name);
                        }
                    }
                    p.sections._tags.fields.tags.tags = [];
                    if( rsp.tags != null ) {
                        for(i in rsp.tags) {
                            p.sections._tags.fields.tags.tags.push(rsp.tags[i].tag.name);
                        }
                    }
                    if( rsp.webcollections != null ) {
                        p.data['_webcollections'] = rsp.webcollections;
                    }
                    p.refresh();
                    p.show(cb);
                });
            } else if( this.edit.sections._webcollections.active == 'yes' ) {
                // Get the list of collections
                M.api.getJSONCb('ciniki.web.collectionList', {'tnid':M.curTenantID}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_blog_postedit.edit;
                    if( rsp.collections != null ) {
                        p.data['_webcollections'] = rsp.collections;
                    }
                    p.refresh();
                    p.show(cb);
                });
            } else {
                this.edit.refresh();
                this.edit.show(cb);
            }
        }*/
    };


    this.deletePost = function() {
        M.confirm('Are you sure you want to delete this post? All information about it will be removed and unrecoverable.',null,function() {
            M.api.getJSONCb('ciniki.blog.postDelete', {'tnid':M.curTenantID, 
                'post_id':M.ciniki_blog_postedit.edit.post_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_blog_post.post.close();
                });
        });
    };
}
