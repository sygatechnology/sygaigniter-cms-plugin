<?php
namespace Plugin\cms;

use \Config\Hooks;

class Filters extends Hooks {
    // Makes reading things below nicer,
	// and simpler to change out script that's used.
	public $aliases = [];

	// Always applied before every request
	public $globals = [];

	// Works on all of a particular HTTP method
	// (GET, POST, etc) as BEFORE filters only
	//     like: 'post' => ['CSRF', 'throttle'],
	public $methods = [];

	// List filter aliases and any before/after uri patterns
	// that they should run on, like:
	//    'isLoggedIn' => ['before' => ['account/*', 'profiles/*']],
    public $filters = [];
}