//
// This app will handle the listing, additions and deletions of blog.  These are associated business.
//
function ciniki_blog_main() {
	//
	// Panels
	//
	this.init = function() {
		//
		// blog panel
		//
		this.menu = new M.panel('Blog',
			'ciniki_blog_main', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.blog.main.menu');
		this.menu.blogtype = 'blog';
        this.menu.sections = {
//			'blogtypes':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'blog', 'tabs':{
//				'blog':{'label':'Public', 'fn':'M.ciniki_blog_main.showMenu(null,\'blog\');'},
//				'memberblog':{'label':'Members', 'fn':'M.ciniki_blog_main.showMenu(null,\'memberblog\');'},
//				}},
			'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':2, 'hint':'search blog', 
				'cellClasses':['multiline','multiline'],
				'noData':'No blog posts found',
				},
			'upcoming':{'label':'Upcoming', 'visible':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['multiline', 'multiline'],
				'noData':'No upcoming posts'
				},
			'drafts':{'label':'Drafts', 'visible':'yes', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['multiline', 'multiline'],
				'noData':'No draft posts',
				'addTxt':'Add',
				'addFn':'M.startApp(\'ciniki.blog.postedit\',null,\'M.ciniki_blog_main.showMenu();\',\'mc\',{\'post_id\':0,\'blogtype\':M.ciniki_blog_main.menu.blogtype});',
				},
			'past':{'label':'Posts', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['multiline', 'multiline'],
				'noData':'No posts',
				'limit':5,
				'moreTxt':'more',
				'moreFn':'M.ciniki_blog_main.showPosts(\'M.ciniki_blog_main.showMenu();\',\'now\',0,M.ciniki_blog_main.menu.blogtype);',
				},
			};
		this.menu.liveSearchCb = function(s, i, value) {
			if( s == 'search' && value != '' ) {
				M.api.getJSONBgCb('ciniki.blog.postSearch', {'business_id':M.curBusinessID, 
					'start_needle':value, 'limit':'10', 'blogtype':M.ciniki_blog_main.menu.blogtype}, function(rsp) { 
						M.ciniki_blog_main.menu.liveSearchShow('search', null, M.gE(M.ciniki_blog_main.menu.panelUID + '_' + s), rsp.posts); 
					});
				return true;
			}
		};
		this.menu.liveSearchResultValue = function(s, f, i, j, d) {
			switch(j) {
				case 0: return '<span class="maintext">' + d.post.publish_date + '</span><span class="subtext">' + d.post.publish_time + '</span>';
				case 1: return '<span class="maintext">' + d.post.title + '</span><span class="subtext">' + d.post.excerpt + '</span>';
			}
			return '';
		}
		this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
			return 'M.startApp(\'ciniki.blog.post\',null,\'M.ciniki_blog_main.showMenu();\',\'mc\',{\'post_id\':\'' + d.post.id + '\',\'blogtype\':M.ciniki_blog_main.menu.blogtype});';
		};
		this.menu.liveSearchSubmitFn = function(s, search_str) {
			M.ciniki_blog_main.searchPosts('M.ciniki_blog_main.showMenu();', search_str);
		};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.noData = function(s) { return this.sections[s].noData; }
		this.menu.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return '<span class="maintext">' + d.post.publish_date + '</span><span class="subtext">' + d.post.publish_time + '</span>';
				case 1: return '<span class="maintext">' + d.post.title + '</span><span class="subtext">' + d.post.excerpt + '</span>';
			}
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.blog.post\',null,\'M.ciniki_blog_main.showMenu();\',\'mc\',{\'post_id\':\'' + d.post.id + '\',\'blogtype\':M.ciniki_blog_main.menu.blogtype});';
		};
		this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.blog.postedit\',null,\'M.ciniki_blog_main.showMenu();\',\'mc\',{\'post_id\':0,\'blogtype\':M.ciniki_blog_main.menu.blogtype});');
		this.menu.addClose('Back');

		//
		// The posts panel for finding posts by year/month
		//
		this.posts = new M.panel('Posts',
			'ciniki_blog_main', 'posts',
			'mc', 'medium', 'sectioned', 'ciniki.blog.main.posts');
		this.posts.year = 0;
		this.posts.month = 0;
		this.posts.blogtype = 'blog';
		this.posts.sections = {
			'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
			'months':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':'0', 'tabs':{
				'0':{'label':'All', 'fn':'M.ciniki_blog_main.showPosts(null,null,0);'},
				'1':{'label':'Jan', 'fn':'M.ciniki_blog_main.showPosts(null,null,1);'},
				'2':{'label':'Feb', 'fn':'M.ciniki_blog_main.showPosts(null,null,2);'},
				'3':{'label':'Mar', 'fn':'M.ciniki_blog_main.showPosts(null,null,3);'},
				'4':{'label':'Apr', 'fn':'M.ciniki_blog_main.showPosts(null,null,4);'},
				'5':{'label':'May', 'fn':'M.ciniki_blog_main.showPosts(null,null,5);'},
				'6':{'label':'Jun', 'fn':'M.ciniki_blog_main.showPosts(null,null,6);'},
				'7':{'label':'Jul', 'fn':'M.ciniki_blog_main.showPosts(null,null,7);'},
				'8':{'label':'Aug', 'fn':'M.ciniki_blog_main.showPosts(null,null,8);'},
				'9':{'label':'Sep', 'fn':'M.ciniki_blog_main.showPosts(null,null,9);'},
				'10':{'label':'Oct', 'fn':'M.ciniki_blog_main.showPosts(null,null,10);'},
				'11':{'label':'Nov', 'fn':'M.ciniki_blog_main.showPosts(null,null,11);'},
				'12':{'label':'Dec', 'fn':'M.ciniki_blog_main.showPosts(null,null,12);'},
				}},
			'posts':{'label':'', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['multiline','multiline'],
				'noData':'No posts found',
				},
			};
		this.posts.noData = function(s) { return this.sections[s].noData; }
		this.posts.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return '<span class="maintext">' + d.post.publish_date + '</span><span class="subtext">' + d.post.publish_time + '</span>';
				case 1: return '<span class="maintext">' + d.post.title + '</span><span class="subtext">' + d.post.excerpt + '</span>';
			}
		};
		this.posts.rowFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.blog.post\',null,\'M.ciniki_blog_main.showPosts();\',\'mc\',{\'post_id\':\'' + d.post.id + '\',\'blogtype\':M.ciniki_blog_main.posts.blogtype});';
		};
		this.posts.sectionData = function(s) { return this.data[s]; }
		this.posts.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_blog_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		if( args.blogtype != null && args.blogtype != '' ) {
			this.showMenu(cb, args.blogtype);
		} else {
			this.showMenu(cb, 'blog');
		}
	}

	this.showMenu = function(cb, blogtype) {
		this.menu.data = {};
		if( blogtype != null && blogtype != '' ) { this.menu.blogtype = blogtype; }
		M.api.getJSONCb('ciniki.blog.postStats', 
			{'business_id':M.curBusinessID, 'drafts':'yes', 'upcoming':'yes', 'past':11, 
				'years':'yes', 'blogtype':this.menu.blogtype}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_blog_main.menu;
				p.data.upcoming = rsp.upcoming;
				p.data.drafts = rsp.drafts;
				p.data.past = rsp.past;
				if( rsp.min_year != null ) {
					M.ciniki_blog_main.setupYears(rsp.min_year, rsp.max_year);
				}
				p.sections.upcoming.visible=(rsp.upcoming!=null&&rsp.upcoming.length>0)?'yes':'no';
				p.refresh();
				p.show(cb);
			});
	};

	this.setupYears = function(min_year) {
		this.posts.sections.years.tabs = {};
		if( min_year == null || min_year == '' || min_year < 1970 ) {
			min_year = new Date().getFullYear();
		} 
		cur_year = new Date().getFullYear();
		for(i=min_year;i<=cur_year;i++) {
			this.posts.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_blog_main.showPosts(null,' + i + ',null);'};
		}
		this.posts.sections.years.visible=(min_year==cur_year)?'no':'yes';
	};

	this.showPosts = function(cb, year, month, blogtype) {
		if( year != null ) {
			if( year == 'now' ) {
				this.posts.year = new Date().getFullYear();
				this.posts.sections.years.selected = this.posts.year;
			} else {
				this.posts.year = year;
				this.posts.sections.years.selected = year;
			}
		}
		if( month != null ) {
			this.posts.month = month;
			this.posts.sections.months.selected = month;
		}
		if( blogtype != null ) {
			this.posts.blogtype = blogtype;
		}
		this.posts.reset();
		M.api.getJSONCb('ciniki.blog.postList', {'business_id':M.curBusinessID, 
			'year':this.posts.year, 'month':this.posts.month, 'status':'40','blogtype':this.posts.blogtype}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_blog_main.posts;
				p.data = {};
				p.data.posts = rsp.posts;
				p.refresh();
				p.show(cb);
			});
	};
};
