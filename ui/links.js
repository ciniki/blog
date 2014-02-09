//
function ciniki_blog_postlinks() {
	//
	// Panels
	//
	this.init = function() {
		//
		// The panel to edit an existing reference
		//
		this.edit = new M.panel('Link',
			'ciniki_blog_postlinks', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.blog.recipes.edit');
		this.edit.data = {};
		this.edit.link_id = 0;
		this.edit.sections = {
			'link':{'label':'Link', 'fields':{
				'name':{'label':'Name', 'hint':'', 'type':'text'},
				'url':{'label':'URL', 'hint':'Enter the http:// address', 'type':'text'},
				}},
			'_description':{'label':{'Additional Information', 'fields':{
				'description':{'label':'', 'hidelabel':'yes', 'hint':'Add additional information about your link', 'type':'textarea'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save Link', 'fn':'M.ciniki_blog_postlinks.saveLink();'},
				'delete':{'label':'Delete Link', 'fn':'M.ciniki_blog_postlinks.deleteLink();'},
				}},
			};
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.blog.postLinkHistory', 'args':{'business_id':M.curBusinessID, 
				'link_id':this.link_id, 'field':i}};
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_blog_postlinks.saveLink();');
		this.edit.addClose('cancel');
	};

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_blog_postlinks', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		if( args.link_id != null && args.link_id > 0 ) {
			// Edit an existing reference
			this.showEdit(cb, 0, args.link_id);
		} else if( args.post_id != null && args.post_id > 0 ) {
			// Add a new reference for a post
			this.showEdit(cb, args.post_id, 0);
		}
	};

	this.showEdit = function(cb, pid, lid) {
		if( pid != null ) { this.edit.post_id = pid; }
		if( lid != null ) { this.edit.link_id = lid; }
		if( this.edit.link_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.blog.postLinkGet', 
				{'business_id':M.curBusinessID, 'link_id':this.edit.link_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_blog_postlinks.edit;
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

	this.saveLink = function() {
		if( this.edit.link_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.blog.postLinkUpdate', 
					{'business_id':M.curBusinessID, 'link_id':this.edit.link_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_blog_postlinks.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.blog.postLinkAdd', 
					{'business_id':M.curBusinessID, 'post_id':this.edit.post_id, 
					'object':'ciniki.recipes.recipe'}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_blog_postlinks.edit.close();
					});
			} else {
				this.edit.close();
			}
		}
	};

	this.deleteLink = function() {
		if( confirm("Are you sure you want to remove this recommended recipe?") ) {
			var rsp = M.api.getJSONCb('ciniki.blog.postLinkDelete', 
				{'business_id':M.curBusinessID, 'link_id':this.edit.link_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_blog_postlinks.edit.close();
				});
		}	
	};
}
