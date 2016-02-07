//
// This is the main UI for a blog post
//
function ciniki_blog_post() {
	this.statusOptions = {
		'10':'Draft',
		'40':'Published',
		'60':'Deleted',
		};
	this.init = function() {
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
				'addFn':'M.startApp(\'ciniki.blog.postimages\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id,\'add\':\'yes\'});',
				},
			'links':{'label':'Links', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline'],
				'noData':'No post links',
				'addTxt':'Add Link',
				'addFn':'M.startApp(\'ciniki.blog.postlinks\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id,\'add\':\'yes\'});',
				},
			'files':{'label':'Files', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline'],
				'noData':'No post files',
				'addTxt':'Add File',
				'addFn':'M.startApp(\'ciniki.blog.postfiles\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id,\'add\':\'yes\'});',
				},
//			'recipes':{'label':'Recipes', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
//				'addTxt':'Add recipe',
//				'addFn':'M.startApp(\'ciniki.blog.postrecipes\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id});',
//				},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.startApp(\'ciniki.blog.postedit\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id});'},
				}},
		};
		this.post.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.blog.postImageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid, 'post_id':M.ciniki_blog_post.post.post_id});
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
				var rsp = M.api.getJSONCb('ciniki.blog.postGet', {'business_id':M.curBusinessID, 
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
		};
		this.post.rowFn = function(s, i, d) {	
			if( s == 'links' ) {
				return 'M.startApp(\'ciniki.blog.postlinks\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'link_id\':\'' + d.link.id + '\'});';
			}
			if( s == 'files' ) {
				return 'M.startApp(\'ciniki.blog.postfiles\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'file_id\':\'' + d.file.id + '\'});';
			}
			if( s == 'products' ) {
				return 'M.startApp(\'ciniki.blog.postproduct\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id,\'ref_id\':\'' + d.product.ref_id + '\'});';
			}
			if( s == 'recipes' ) {
				return 'M.startApp(\'ciniki.blog.postrecipes\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id,\'ref_id\':\'' + d.recipe.ref_id + '\'});';
			}
		};
		this.post.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.blog.postimages\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'post_image_id\':\'' + d.image.id + '\'});';
		};
		this.post.emailSubscribers = function(test) {
			if( this.data.mailing_id != null && this.data.mailing_id > 0 ) {
				if( confirm('Are you sure the article is correct and ready to send?') ) {
					M.api.getJSONCb('ciniki.mail.mailingSend', {'business_id':M.curBusinessID,
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
		};
		this.post.addButton('edit', 'Edit', 'M.startApp(\'ciniki.blog.postedit\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'post_id\':M.ciniki_blog_post.post.post_id});');
		this.post.addClose('Back');
	};

	this.start = function(cb, aP, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }
		var aC = M.createContainer(aP, 'ciniki_blog_post', 'yes');
		if( aC == null ) {
			alert('App Error');
			return false;
		}

		this.post.sections.info.list.subtitle.visible = (M.curBusiness.modules['ciniki.blog'] != null && (M.curBusiness.modules['ciniki.blog'].flags&0x010000))>0?'yes':'no';

		//
		// Check if web collections are enabled
		//
		if( M.curBusiness.modules['ciniki.web'] != null 
			&& (M.curBusiness.modules['ciniki.web'].flags&0x08) ) {
			this.post.sections.info.list.webcollections_text.visible = 'yes';
		} else {
			this.post.sections.info.list.webcollections_text.visible = 'no';
		}

		if( args.post_id != null && args.post_id > 0 ) {
			this.showPost(cb, args.post_id);
		}
	}

	this.showPost = function(cb, pid) {
		this.post.reset();
		var numBlogs = 0;
		if( (M.curBusiness.modules['ciniki.blog'].flags&0x0001) > 0 ) {
			numBlogs++; 
		}
		if( (M.curBusiness.modules['ciniki.blog'].flags&0x0100) > 0 ) {
			numBlogs++; 
		}
		if( numBlogs > 1 ) {
			this.post.sections.info.list.publish_to_text.visible = 'yes';
		} else {
			this.post.sections.info.list.publish_to_text.visible = 'no';
		}
//		this.post.sections.recipes.visible=(M.curBusiness.modules['ciniki.recipes']!=null)?'yes':'no';
		if( pid != null ) { this.post.post_id = pid; }
		M.api.getJSONCb('ciniki.blog.postGet', {'business_id':M.curBusinessID,
			'post_id':this.post.post_id, 'files':'yes', 'images':'yes', 
			'links':'yes', 'refs':'yes', 'webcollections':'yes'}, function(rsp) {
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
				if( M.curBusiness.modules['ciniki.mail'] != null
					&& M.curBusiness.modules['ciniki.subscriptions'] != null 
					&& (M.curBusiness.modules['ciniki.blog'].flags&0x7000) > 0 	// Blog subscriptions active
					&& p.data.subscriptions != null && p.data.subscriptions.length > 0	// There are subscriptions
					&& (rsp.post.publish_to&0x01) == 1	// Published to public blog
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
				p.sections.info.list.categories.visible=(M.curBusiness.modules['ciniki.blog'].flags&0x222)>0?'yes':'no';
				p.sections.info.list.tags.visible=(M.curBusiness.modules['ciniki.blog'].flags&0x444)>0?'yes':'no';
				p.refresh();
				p.show(cb);
			});
	};
}
