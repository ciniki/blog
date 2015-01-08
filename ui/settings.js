//
function ciniki_blog_settings() {
	this.toggleOptions = {'no':' No ', 'yes':' Yes '};

	this.init = function() {
		//
		// The main panel, which lists the options for production
		//
		this.main = new M.panel('Blog Settings',
			'ciniki_blog_settings', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.blog.settings.main');
		this.main.sections = {
			'options':{'label':'', 'fields':{
				'mailing-subject-prepend':{'label':'Mailing Subject', 'type':'text'},
			}},
		};
		this.main.fieldValue = function(s, i, d) { 
			return this.data[i];
		};

		//  
		// Callback for the field history
		//  
		this.main.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.blog.settingsHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
		};

		this.main.addButton('save', 'Save', 'M.ciniki_blog_settings.saveSettings();');
		this.main.addClose('Cancel');
	}

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
		var appContainer = M.createContainer(appPrefix, 'ciniki_blog_settings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.showMain(cb);
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMain = function(cb) {
		var rsp = M.api.getJSONCb('ciniki.blog.settingsGet', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_blog_settings.main;
				p.data = rsp.settings;
				p.refresh();
				p.show(cb);
			});
	}

	this.saveSettings = function() {
		var c = this.main.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSONCb('ciniki.blog.settingsUpdate', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_blog_settings.main.close();
				});
		} else {
			M.ciniki_blog_settings.main.close();
		}
	}
}
