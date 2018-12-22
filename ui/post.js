//
// This is the main UI for a blog post
//
function ciniki_blog_post() {
    this.statusOptions = {
        '10':'Draft',
        '40':'Published',
        '60':'Deleted',
        };
    //
    // The post panel
    //
    this.post = new M.panel('Post',
        'ciniki_blog_post', 'post',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.blog.post.post');
    this.post.data = {};
    this.post.post_id = 0;
    this.post.sections = {
        '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
            }},
        '_image_caption':{'label':'', 'aside':'yes', 'visible':function() {return M.ciniki_blog_post.post.data.primary_image_caption!=''?'yes':'no';}, 'list':{
            'primary_image_caption':{'label':'Caption', 'type':'text'},
            }},
        'info':{'label':'', 'aside':'yes', 'list':{
            'title':{'label':'Title'},
            'subtitle':{'label':'Subtitle', 'visible':'no'},
            'publish_date':{'label':'Date'},
            'status_text':{'label':'Status'},
            'publish_to_text':{'label':'Publish To', 'visible':'no'},
            'webcollections_text':{'label':'Web Collections'},
            'categories':{'label':'Categories', 'visible':'no'},
            'tags':{'label':'Keywords', 'visible':'no'},
            }},
        'subscriptions':{'label':'Subscriptions', 'aside':'yes', 'visible':'no', 'list':{}},
        '_subscription_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'emailtest':{'label':'Send Test Email', 'visible':'no', 'fn':'M.ciniki_blog_post.post.emailSubscribers(\'yes\');'},
            'email':{'label':'Email Subscribers', 'visible':'no', 'fn':'M.ciniki_blog_post.post.emailSubscribers(\'no\');'},
            }},
        'excerpt':{'label':'Synopsis', 'type':'htmlcontent'},
        'content':{'label':'Post', 'type':'htmlcontent'},
        'images':{'label':'Gallery', 'type':'simplethumbs'},
        '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Additional Image',
            'addFn':'M.startApp(\'ciniki.blog.postimages\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id,\'add\':\'yes\'});',
            },
        'links':{'label':'Links', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['multiline'],
            'noData':'No post links',
            'addTxt':'Add Link',
            'addFn':'M.startApp(\'ciniki.blog.postlinks\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id,\'add\':\'yes\'});',
            },
        'files':{'label':'Files', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['multiline'],
            'noData':'No post files',
            'addTxt':'Add File',
            'addFn':'M.startApp(\'ciniki.blog.postfiles\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id,\'add\':\'yes\'});',
            },
        'audio':{'label':'Audio', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['multiline'],
            'noData':'No audio files',
            'addTxt':'Add Audio File',
            'addFn':'M.ciniki_blog_post.audio.open(\'M.ciniki_blog_post.post.open();\',0,M.ciniki_blog_post.post.post_id)',
            },
//          'recipes':{'label':'Recipes', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
//              'addTxt':'Add recipe',
//              'addFn':'M.startApp(\'ciniki.blog.postrecipes\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id});',
//              },
        '_buttons':{'label':'', 'buttons':{
            'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.blog.postedit\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id});'},
            }},
    };
    this.post.addDropImage = function(iid) {
        var rsp = M.api.getJSON('ciniki.blog.postImageAdd',
            {'tnid':M.curTenantID, 'image_id':iid, 'post_id':M.ciniki_blog_post.post.post_id});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        return true;
    };
    this.post.sectionData = function(s) {
        if( s == 'info' || s == 'subscriptions' || s == '_image_caption' ) { return this.sections[s].list; }
        if( s == 'excerpt' || s == 'content' ) { return this.data[s].replace(/\n/g, '<br/>'); }
        return this.data[s];
    };
    this.post.addDropImageRefresh = function() {
        if( M.ciniki_blog_post.post.post_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.blog.postGet', {'tnid':M.curTenantID, 
                'post_id':M.ciniki_blog_post.post.post_id, 'images':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_blog_post.post;
                    p.data.images = rsp.post.images;
                    p.refreshSection('images');
                });
        }
    };
    this.post.listLabel = function(s, i, d) { return d.label; }
    this.post.listValue = function(s, i, d) {
        if( s == 'subscriptions' ) { return d.status; }
        return this.data[i];
    };
    this.post.fieldValue = function(s, i, d) {
        return this.data[i];
    };
    this.post.cellValue = function(s, i, j, d) {
        if( s == 'links' && j == 0 ) {
            return '<span class="maintext">' + d.link.name + '</span><span class="subtext">' + d.link.url + '</span>';
        }
        if( s == 'files' && j == 0 ) {
            return '<span class="maintext">' + d.file.name + '</span>';
        }
        if( s == 'products' && j == 0 ) {
            return d.product.name;
        }
        if( s == 'recipes' && j == 0 ) {
            return d.recipe.name;
        }
        if( s == 'audio' && j == 0 ) {
            if( (d.flags&0x02) == 0x02 ) {
                return d.name + ' <span class="subdue">(Processing)</span>';
            }
            return d.name;
        }
    }
    this.post.rowFn = function(s, i, d) {   
        if( s == 'links' ) {
            return 'M.startApp(\'ciniki.blog.postlinks\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'link_id\':\'' + d.link.id + '\'});';
        }
        if( s == 'files' ) {
            return 'M.startApp(\'ciniki.blog.postfiles\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'file_id\':\'' + d.file.id + '\'});';
        }
        if( s == 'products' ) {
            return 'M.startApp(\'ciniki.blog.postproduct\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id,\'ref_id\':\'' + d.product.ref_id + '\'});';
        }
        if( s == 'recipes' ) {
            return 'M.startApp(\'ciniki.blog.postrecipes\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id,\'ref_id\':\'' + d.recipe.ref_id + '\'});';
        }
        if( s == 'audio' ) {
            return 'M.ciniki_blog_post.audio.open(\'M.ciniki_blog_post.post.open();\',\'' + d.id + '\',M.ciniki_blog_post.post.post_id);';
        }
    }
    this.post.thumbFn = function(s, i, d) {
        return 'M.startApp(\'ciniki.blog.postimages\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'post_image_id\':\'' + d.image.id + '\'});';
    }
    this.post.emailSubscribers = function(test) {
        if( this.data.mailing_id != null && this.data.mailing_id > 0 ) {
            if( confirm('Are you sure the article is correct and ready to send?') ) {
                M.api.getJSONCb('ciniki.mail.mailingSend', {'tnid':M.curTenantID,
                    'mailing_id':this.data.mailing_id, 'test':test}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        if( test == 'yes' ) {
                            alert('Email sent, please check your email');
                        } else {
                            alert('Queueing and sending emails');
                        }
                    });
            }
        }
    }
    this.post.open = function(cb, pid) {
        this.reset();
        var numBlogs = 0;
        if( (M.curTenant.modules['ciniki.blog'].flags&0x0001) > 0 ) {
            numBlogs++; 
        }
        if( (M.curTenant.modules['ciniki.blog'].flags&0x0100) > 0 ) {
            numBlogs++; 
        }
        if( numBlogs > 1 ) {
            this.sections.info.list.publish_to_text.visible = 'yes';
        } else {
            this.sections.info.list.publish_to_text.visible = 'no';
        }
        if( pid != null ) { this.post_id = pid; }
        M.api.getJSONCb('ciniki.blog.postGet', {'tnid':M.curTenantID,
            'post_id':this.post_id, 'files':'yes', 'images':'yes', 
            'links':'yes', 'audio':'yes', 'refs':'yes', 'webcollections':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_blog_post.post;
                p.data = rsp.post;
                if( rsp.post.categories != null && rsp.post.categories != '' ) {
                    p.data.categories = rsp.post.categories.replace(/::/g, ', ');
                }
                //
                // Check if mail and subscriptions are active
                //
                p.sections.subscriptions.visible = 'no';
                if( M.curTenant.modules['ciniki.mail'] != null
                    && M.curTenant.modules['ciniki.subscriptions'] != null 
                    && (M.curTenant.modules['ciniki.blog'].flags&0x7000) > 0  // Blog subscriptions active
                    && p.data.subscriptions != null && p.data.subscriptions.length > 0  // There are subscriptions
                    && (rsp.post.publish_to&0x01) == 1  // Published to public blog
                    ) {
                    p.sections._subscription_buttons.buttons.emailtest.visible = 'no';
                    p.sections._subscription_buttons.buttons.email.visible = 'no';
                    var eml = 'no';
                    // Build active subscription list only
                    p.sections.subscriptions.list = {};
                    for(i in rsp.post.subscriptions) {
                        if( rsp.post.subscriptions[i].subscription.status == 'yes' ) {
                            p.sections.subscriptions.visible = 'yes';
                            p.sections.subscriptions.list[i] = {'label':rsp.post.subscriptions[i].subscription.name,
                                'status':rsp.post.subscriptions[i].subscription.mailing_status_text};
                            if( rsp.post.subscriptions[i].subscription.mailing_status == 10 
                                && rsp.post.mailing_id > 0
                                ) {
                                eml = 'yes';
                            }
                        }
                    }
                    if( rsp.post.status == '40' && eml == 'yes') {
                        // Check if any subscription still are unsent
                        p.sections._subscription_buttons.buttons.emailtest.visible = 'yes';
                        p.sections._subscription_buttons.buttons.email.visible = 'yes';
                    }
                } else {
                    p.sections.subscriptions.visible = 'no';
                    p.sections._subscription_buttons.buttons.emailtest.visible = 'no';
                    p.sections._subscription_buttons.buttons.email.visible = 'no';
                }
                if( rsp.post.tags != null && rsp.post.tags != '' ) {
                    p.data.tags = rsp.post.tags.replace(/::/g, ', ');
                }
                p.sections.info.list.categories.visible=(M.curTenant.modules['ciniki.blog'].flags&0x222)>0?'yes':'no';
                p.sections.info.list.tags.visible=(M.curTenant.modules['ciniki.blog'].flags&0x444)>0?'yes':'no';
                p.refresh();
                p.show(cb);
            });
    }
    this.post.addButton('edit', 'Edit', 'M.startApp(\'ciniki.blog.postedit\',null,\'M.ciniki_blog_post.post.open();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id});');
    this.post.addClose('Back');

    //
    // The audio file panel
    //
    this.audio = new M.panel('Edit Audio', 'ciniki_blog_post', 'audio', 'mc', 'medium', 'sectioned', 'ciniki.blog.post.audio');
    this.audio.data = {};
    this.audio.post_id = 0;
    this.audio.post_audio_id = 0;
    this.audio.sections = {
        'info':{'label':'Information', 'type':'simpleform', 'fields':{
            'name':{'label':'Title', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
        }},
        '_addfile':{'label':'Add File', 
            'active':function() { return M.ciniki_blog_post.audio.post_audio_id > 0 ? 'no' : 'yes'; },
            'fields':{
                'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
        }},
//        '_file':{'label':'Audio File', 
//            'visible':function() { return M.ciniki_blog_post.audio.post_audio_id > 0 ? 'no' : 'yes';},
//            'fields':{
//                'audio_id':{'label':'Audio', 'hidelabel':'yes', 'type':'audio_id', 'controls':'all', 'history':'no'},
//        }},
/*            '_audio_mp3':{'label':'MP3 File', 'fields':{
            'mp3_audio_id':{'label':'MP3', 'hidelabel':'yes', 'type':'audio_id', 'controls':'all', 'history':'no'},
        }},
        '_audio_wav':{'label':'WAV File', 'fields':{
            'wav_audio_id':{'label':'WAV', 'hidelabel':'yes', 'type':'audio_id', 'controls':'all', 'history':'no'},
        }},
        '_audio_ogg':{'label':'OGG File', 'fields':{
            'ogg_audio_id':{'label':'OGG', 'hidelabel':'yes', 'type':'audio_id', 'controls':'all', 'history':'no'},
        }}, */
        '_description':{'label':'Description', 'type':'simpleform', 'fields':{
            'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
        }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_blog_post.audio.save();'},
            'delete':{'label':'Delete', 
                'visible':function() { return M.ciniki_blog_post.audio.post_audio_id > 0 ? 'yes' : 'no';},
                'fn':'M.ciniki_blog_post.audio.remove();'},
        }},
    };
    this.audio.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) {
            return this.data[i]; 
        } 
        return ''; 
    };
    this.audio.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.blog.postAudioHistory', 'args':{'tnid':M.curTenantID, 
            'post_audio_id':this.post_audio_id, 'field':i}};
    };
/*    this.audio.addDropFile = function(s, i, iid, file) {
        if( file.type == 'audio/flac' || file.type == 'audio/flac' ) {
            M.ciniki_blog_post.audio.setFieldValue('flac_audio_id', iid);
            M.gE(this.panelUID + '_mp3_audio_id_audio_filename').innerHTML = file.name;
        }
        if( file.type == 'audio/mpeg' || file.type == 'audio/mp3' ) {
            M.ciniki_blog_post.audio.setFieldValue('mp3_audio_id', iid);
            M.gE(this.panelUID + '_mp3_audio_id_audio_filename').innerHTML = file.name;
        }
        else if( file.type == 'audio/vnd.wave' || file.type == 'audio/wav' ) {
            M.ciniki_blog_post.audio.setFieldValue('wav_audio_id', iid);
            M.gE(this.panelUID + '_wav_audio_id_audio_filename').innerHTML = file.name;
        }
        else if( file.type == 'audio/ogg' ) {
            M.ciniki_blog_post.audio.setFieldValue('ogg_audio_id', iid);
            M.gE(this.panelUID + '_ogg_audio_id_audio_filename').innerHTML = file.name;
        }
        return true;
    }; */
/*    this.audio.deleteFile = function(i) {
        M.ciniki_blog_post.audio.setFieldValue(i, 0);
    }; */
/*    this.edit.addDropFileRefresh = function() {
//          if( M.ciniki_blog_post.edit.product_audio_id > 0 ) {
//              M.api.getJSONCb('ciniki.products.audioGet', {'tnid':M.curTenantID, 
//                  'product_audio_id':M.ciniki_blog_post.edit.product_audio_id, 'audio':'yes'}, function(rsp) {
////                        if( rsp.stat != 'ok' ) {
//                          M.api.err(rsp);
//                          return false;
//                      }
//                      var p = M.ciniki_blog_post.edit;
//                      p.data = rsp.audio;
//                      p.refreshSection('_audio');
//                  });
//          } else {
// FIXME: Add code to update audio section
//              M.api.getJSONCb('ciniki.audio.get', {'tnid':M.curTenantID, 
//                  'audio_id':M.ciniki_blog_post.edit.product_id, 'audio':'yes'}, function(rsp) {
//                      if( rsp.stat != 'ok' ) {
//                          M.api.err(rsp);
//                          return false;
//                      }
//                      var p = M.ciniki_products_product.edit;
//                      p.data.images = rsp.product.images;
//                      p.refreshSection('_audio');
//                  });
//          }
    }; */
    this.audio.open = function(cb, aid, pid) {
        if( aid != null ) { this.post_audio_id = aid; }
        if( pid != null ) { this.post_id = pid; }
        M.api.getJSONCb('ciniki.blog.postAudioGet', {'tnid':M.curTenantID, 'post_audio_id':this.post_audio_id, 'post_id':this.post_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_blog_post.audio;
            p.data = rsp.audio;
            p.refresh();
            p.show(cb);
        });
    }
    this.audio.save = function() {
        if( this.post_audio_id > 0 ) {
            var c = this.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.blog.postAudioUpdate', {'tnid':M.curTenantID, 'post_audio_id':this.post_audio_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_blog_post.audio.close();
                        }
                    });
            } else {
                this.audio.close();
            }
        } else {
            var c = this.serializeFormData('yes');
            M.api.postJSONFormData('ciniki.blog.postAudioAdd', {'tnid':M.curTenantID, 'post_id':this.post_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } else {
                    M.ciniki_blog_post.audio.close();
                }
            });
        }
    }
    this.audio.remove = function() {
        if( confirm('Are you sure you want to delete this audio?') ) {
            M.api.getJSONCb('ciniki.blog.postAudioDelete', {'tnid':M.curTenantID, 'post_audio_id':this.post_audio_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_blog_post.audio.close();
            });
        }
    }
    this.audio.addButton('save', 'Save', 'M.ciniki_blog_post.audio.save();');
    this.audio.addClose('Cancel');

    this.start = function(cb, aP, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }
        var aC = M.createContainer(aP, 'ciniki_blog_post', 'yes');
        if( aC == null ) {
            alert('App Error');
            return false;
        }

        this.post.sections.info.list.subtitle.visible = (M.curTenant.modules['ciniki.blog'] != null && (M.curTenant.modules['ciniki.blog'].flags&0x010000))>0?'yes':'no';

        //
        // Check if web collections are enabled
        //
        if( M.curTenant.modules['ciniki.web'] != null 
            && (M.curTenant.modules['ciniki.web'].flags&0x08) ) {
            this.post.sections.info.list.webcollections_text.visible = 'yes';
        } else {
            this.post.sections.info.list.webcollections_text.visible = 'no';
        }

        if( args.post_id != null && args.post_id > 0 ) {
            this.post.open(cb, args.post_id);
        }
    }
}
