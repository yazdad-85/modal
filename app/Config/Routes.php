<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'AuthController::loginForm');
$routes->get('login', 'AuthController::loginForm');
$routes->post('login', 'AuthController::login');
$routes->get('logout', 'AuthController::logout');

$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('projects/create', 'ProjectController::create');
    $routes->post('projects', 'ProjectController::store');
    $routes->get('projects/(:num)', 'ProjectController::show/$1');
    $routes->get('projects/(:num)/edit', 'ProjectController::edit/$1');
    $routes->post('projects/(:num)', 'ProjectController::update/$1');
    $routes->post('projects/(:num)/delete', 'ProjectController::delete/$1');
    $routes->post('projects/(:num)/transactions', 'ProjectController::storeTransaction/$1');
    $routes->post('projects/(:num)/transactions/(:num)/delete', 'ProjectController::deleteTransaction/$1/$2');
    $routes->get('projects/(:num)/export/pdf', 'ExportController::pdf/$1');
    $routes->get('projects/(:num)/export/excel', 'ExportController::excel/$1');
});
