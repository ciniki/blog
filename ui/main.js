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
        this.menu.sections = {
//			'search':{'label':'', 'type':'},
			'upcoming':{'label':'Upcoming', 'visible':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No upcoming posts'
				},
			'drafts':{'label':'Drafts', 'visible':'no', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No draft posts'
				},
			'past':{'label':'Posts', 'type':'simplegrid', 'num_cols':2,
				'headerValues':null,
				'cellClasses':['', ''],
				'noData':'No blog'
				'limit':10,
				'moreTxt':'more',
				'moreFn':'M.ciniki_blog_main.showPosts(\'M.ciniki_blog_main.showMenu();\',\'now\');',
				},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.noData = function(s) { return this.sections[s].noData; }
		this.menu.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return this.post.publish_date;
				case 1: return this.post.title;
			}
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.blog.post\',null,\'M.ciniki_blog_main.showMenu();\',\'mc\',{\'post_id\':\'' + d.post.id + '\'});';
		};
		this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.blog.post\',null,\'M.ciniki_blog_main.showMenu();\',\'mc\',{\'post_id\':0});');
		this.menu.addClose('Back');

		//
		// The posts panel for finding posts by year/month
		//
		this.posts = new M.panel('Posts',
			'ciniki_blog_main', 'posts',
			'mc', 'medium', 'sectioned', 'ciniki.blog.main.posts');
		this.posts.year = 0;
		this.posts.month = 0;
		this.posts.sections = {
			'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
			'months':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'0', 'tabs':{
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
				'cellClasses':['',''],
				'noData':'No posts found',
				},
			};
		this.posts.noData = function(s) { return this.sections[s].noData; }
		this.posts.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return this.post.publish_date;
				case 1: return this.post.title;
			}
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

		this.showMenu(cb);
	}

	this.showMenu = function(cb) {
		this.menu.data = {};
		M.api.getJSONCb('ciniki.blog.postStats', 
			{'business_id':M.curBusinessID, 
			'drafts':'yes', 'upcoming':'yes', 'past':11, 'years':'yes'}, function(rsp) {
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
	};

	this.showPosts = function(cb, year, month) {
		if( year != null ) {
			if( year = 'now' ) {
				this.posts.year = new Date().getFullYear();
				this.posts.year = year;
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
		this.posts.reset();
		M.api.getJSONCb('ciniki.blog.postList', {'business_id':M.curBusinessID, 
			'year':this.posts.year, 'month':this.posts.month, 'status':'40'}, function(rsp) {
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
