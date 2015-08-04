<?php //> Й <- UTF mark

// CMS configuration file.
// modules has their own config files


// module apply order.
// modules will be processed in the order specified here.
// every module can be used several times.
// module must reside in the folder with the same name

$modules_apply_order = array(
	'auth', // note if you place auth module after admin, you will must login both to CMS and auth module
	'admin',
	'filemanager',
	'redirect',
	'feedback',
	'search',
	'content',
	'db',
	'menu',
	'news',
	'sms',
);

// CMS admin password. Note that it is not depends with "auth" module
define('SECURITY_ADMIN_PASSWORD', '111');

// module directory. slash is required at the end.
define('MODULES_DIR', 'modules/');

// CMS DB storage path. modules may use this database or their own
define('DB_PATH', 'data/cms.sqlite');

// default page to display if nothing requested
define('DEFAULT_PAGE_ALIAS', 'clients');

?>