<?php
    // Post routes
    $routes->get('posts', 'Posts::index');
    $routes->get('posts/(:num)', 'Posts::show/$1');
    $routes->post('posts', 'Posts::create', ['filter' => 'authentication']);
    $routes->put('posts/(:num)', 'Posts::update/$1', ['filter' => 'authentication']);
    $routes->delete('posts/(:any)', 'Posts::delete/$1', ['filter' => 'authentication']);
    
    // Term routes
    $routes->get('terms', 'Terms::index');
    $routes->get('terms/(:num)', 'Terms::show/$1');
    $routes->post('terms', 'Terms::create', ['filter' => 'authentication']);
    $routes->put('terms/(:num)', 'Terms::update/$1', ['filter' => 'authentication']);
    $routes->delete('terms/(:num)', 'Terms::delete/$1', ['filter' => 'authentication']);
    
