<?php
	require_once(__DIR__ . '/../functions.php');

	// Router for requests
	$router = new \Bramus\Router\Router();

	// Templating engine
	$displayEngine = getDisplayEngine();

	if ($config['securecookies']) {
		ini_set('session.cookie_secure', True);
	}
	ini_set('session.cookie_httponly', True);

	// Session storage
	if (isset($config['memcached']) && !empty($config['memcached'])) {
		ini_set('session.save_handler', 'memcached');
		ini_set('session.save_path', $config['memcached']);
	}
	session::init();
	if (!session::exists('csrftoken')) {
		session::set('csrftoken', genUUID());
	}

	$api = new API(DB::get());

	(new SiteRoutes())->addRoutes($router, $displayEngine, $api);
	(new ImageRoutes())->addRoutes($router, $displayEngine, $api);
	(new ServerRoutes())->addRoutes($router, $displayEngine, $api);

	// Check CSRF Tokens.
	$router->before('POST', '.*', function() {
		// Pre-Login, we don't have a CSRF Token assigned.
		if (!session::exists('csrftoken')) { return; }

		if (!array_key_exists('csrftoken', $_POST) || empty($_POST['csrftoken']) || $_POST['csrftoken'] != session::get('csrftoken')) {
			header('HTTP/1.1 403 Forbidden');
			die('Invalid CSRF Token');
		}
	});

	// Begin!
	$router->run();