//
// This is the main UI for a blog post
//
function ciniki_blog_postedit() {
	this.statusOptions = {
		'10':'Draft',
		'40':'Published',
		'60':'Removed',
		};
	this.init = function() {
		//
		// The edit panel
		//
		this.edit = new M.panel('Blog Post',
			'ciniki_blog_postedit', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.blog.postedit.edit');
		this.edit.data = {};
		this.edit.post_id = 0;
//		this.edit.formtab = 'generic';
//		this.edit.formtabs = {'label':'', 'field':'type', 'tabs':{
//			'generic':{'label':'Generic', 'field_id':1, 'form':'generic'},
//			'winekit':{'label':'Wine Kit', 'field_id':64, 'form':'winekit'},
//			}};
		this.edit.forms = {};
		this.edit.forms.generic = {
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 
					'controls':'all', 'history':'no'},
				}},
			'info':{'label':'', 'aside':'yes', 'fields':{
				'title':{'label':'Title', 'hint':'', 'type':'text'},
				'publish_date':{'label':'Date', 'type':'text', 'size':'medium'},
				'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':this.statusOptions},
				}},
			'_categories':{'label':'Categories', 'visible':'no', 'aside':'no', 'fields':{
				'categories':{'label':'', 'hidelabel':'yes', 'active':'no', 'type':'tags', 'tags':[], 'hint':'New Category'},
				}},
			'_tags':{'label':'Tags', 'visible':'no', 'aside':'no', 'fields':{
				'tags':{'label':'', 'hidelabel':'yes', 'active':'no', 'type':'tags', 'tags':[], 'hint':'New Tag'},
				}},
			'_excerpt':{'label':'Excerpt', 'fields':{
				'excerpt':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea', 'size':'small'},
				}},
			'_content':{'label':'Post', 'fields':{
				'content':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea', 'size':'large'},
				}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_blog_postedit.savePost();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_blog_postedit.deletePost();'},
				}},
			};
		this.edit.sections = this.edit.forms.generic;
		this.edit.fieldValue = function(s, i, d) {
			if( this.data[i] != null ) { return this.data[i]; }
			return '';
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.blog.postHistory', 'args':{'business_id':M.curBusinessID,
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
		this.edit.addButton('save', 'Save', 'M.ciniki_blog_postedit.savePost();');
		this.edit.addClose('Cancel');
	};

	this.start = function(cb, aP, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }
		var aC = M.createContainer(aP, 'ciniki_blog_postedit', 'yes');
		if( aC == null ) {
			alert('App Error');
			return false;
		}

		this.showEdit(cb, args.post_id);
	}

	this.showEdit = function(cb, pid) {
		this.edit.reset();
		if( pid != null ) { this.edit.post_id = pid; }
		this.edit.sections._categories.visible=((M.curBusiness.modules['ciniki.blog'].flags&0x01)>0)?'yes':'no';
		this.edit.sections._categories.fields.categories.active=((M.curBusiness.modules['ciniki.blog'].flags&0x01)>0)?'yes':'no';
		this.edit.sections._tags.visible=((M.curBusiness.modules['ciniki.blog'].flags&0x02)>0)?'yes':'no';
		this.edit.sections._tags.fields.tags.active=((M.curBusiness.modules['ciniki.blog'].flags&0x02)>0)?'yes':'no';
		if( this.edit.post_id > 0 ) {
			M.api.getJSONCb('ciniki.blog.postGet', {'business_id':M.curBusinessID,
				'post_id':this.edit.post_id, 'categories':'yes', 'tags':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_blog_postedit.edit;
					p.data = rsp.post;
					p.sections._categories.fields.categories.tags = [];
					if( (M.curBusiness.modules['ciniki.blog'].flags&0x01)>0 && rsp.categories != null ) {
						for(i in rsp.categories) {
							p.sections._categories.fields.categories.tags.push(rsp.categories[i].tag.name);
						}
					}
					p.sections._tags.fields.tags.tags = [];
					if( (M.curBusiness.modules['ciniki.blog'].flags&0x02)>0 && rsp.tags != null ) {
						for(i in rsp.tags) {
							p.sections._tags.fields.tags.tags.push(rsp.tags[i].tag.name);
						}
					}
					p.refresh();
					p.show(cb);
				});
		} else {
			this.edit.post_id = 0;
			this.edit.data = {'status':'10'};
			if( (M.curBusiness.modules['ciniki.blog'].flags&0x03)>0 ) {
				M.api.getJSONCb('ciniki.blog.postTags', {'business_id':M.curBusinessID}, function(rsp) {
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
					p.refresh();
					p.show(cb);
				});
			} else {
				this.edit.refresh();
				this.edit.show(cb);
			}
		}
	};

	this.savePost = function() {
		if( this.edit.post_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.blog.postUpdate',
					{'business_id':M.curBusinessID, 'post_id':this.edit.post_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_blog_postedit.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			M.api.postJSONCb('ciniki.blog.postAdd',
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_blog_postedit.edit.close();
				});
		}
	};

	this.deletePost = function() {
		if( confirm('Are you sure you want to delete this post? All information about it will be removed and unrecoverable.') ) {
			M.api.getJSONCb('ciniki.blog.postDelete', {'business_id':M.curBusinessID, 
				'post_id':M.ciniki_blog_postedit.edit.post_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_blog_postfiles.edit.close();
				});
		}
	};
}