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
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
				}},
			'info':{'label':'', 'aside':'yes', 'list':{
				'title':{'label':'Title'},
				'publish_date':{'label':'Date'},
				'status_text':{'label':'Status'},
				'publish_to_text':{'label':'Publish To', 'visible':'no'},
				'categories':{'label':'Categories', 'visible':'no'},
				'tags':{'label':'Tags', 'visible':'no'},
				}},
			'excerpt':{'label':'Excerpt', 'type':'htmlcontent'},
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
			if( s == 'info' ) { return this.sections[s].list; }
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
		this.post.thumbSrc = function(s, i, d) {
			if( d.image.image_data != null && d.image.image_data != '' ) {
				return d.image.image_data;
			} else {
				return '/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg';
			}
		};
		this.post.thumbTitle = function(s, i, d) {
			if( d.image.name != null ) { return d.image.name; }
			return '';
		};
		this.post.thumbID = function(s, i, d) {
			if( d.image.id != null ) { return d.image.id; }
			return 0;
		};
		this.post.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.blog.postimages\',null,\'M.ciniki_blog_post.showPost();\',\'mc\',{\'post_image_id\':\'' + d.image.id + '\'});';
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
			'post_id':this.post.post_id, 
			'files':'yes', 'images':'yes', 'links':'yes', 'refs':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_blog_post.post;
				p.data = rsp.post;
				if( rsp.post.categories != null && rsp.post.categories != '' ) {
					p.data.categories = rsp.post.categories.replace(/::/g, ', ');
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
