<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('manifest.webmanifest', 'ManifestWeb::index');

// Public Auth
$routes->get('/login',  'Auth::login');
$routes->post('/login', 'Auth::loginPost');
$routes->get('/signup', 'Auth::signup');
$routes->post('/signup', 'Auth::signupPost');
$routes->get('/logout', 'Auth::logout');
$routes->get('/',       'Landing::index');
$routes->get('/privacy',  'Landing::privacy');
$routes->post('/contact', 'Landing::submitContact');

// Public linked (rotating) QR — open in browser on a tablet/kiosk
$routes->get('qr/v/(:segment)', 'QrDisplay::live/$1');

// Employee PWA-style web app (mobile-first)
$routes->get('/employee',                    'EmployeeApp::index');
$routes->get('/employee/login',              'EmployeeApp::index');
$routes->get('/employee/dashboard',          'EmployeeApp::index');
$routes->get('/employee/attendance',         'EmployeeApp::index');
$routes->get('/employee/calendar',           'EmployeeApp::index');
$routes->get('/employee/profile',            'EmployeeApp::index');

// Admin Web Panel (protected by session auth filter)
$routes->group('', ['filter' => 'auth'], function ($routes) {
    $routes->get('dashboard',                    'Dashboard::index');

    // Employees
    $routes->get('employees',                    'Employees::index');
    $routes->get('employees/create',             'Employees::create');
    $routes->post('employees/store',             'Employees::store');
    $routes->get('employees/edit/(:num)',        'Employees::edit/$1');
    $routes->post('employees/update/(:num)',     'Employees::update/$1');
    $routes->post('employees/deactivate/(:num)', 'Employees::deactivate/$1');
    $routes->post('employees/activate/(:num)',   'Employees::activate/$1');

    // QR Codes
    $routes->get('qr-codes',                    'QRCodes::index');
    $routes->get('qr-codes/create',             'QRCodes::create');
    $routes->post('qr-codes/store',             'QRCodes::store');
    $routes->get('qr-codes/show/(:num)',        'QRCodes::show/$1');
    $routes->get('qr-codes/edit/(:num)',        'QRCodes::edit/$1');
    $routes->post('qr-codes/update/(:num)',     'QRCodes::update/$1');
    $routes->post('qr-codes/toggle/(:num)',     'QRCodes::toggle/$1');

    // Attendance
    $routes->get('attendance',                   'Attendance::index');
    $routes->get('attendance/edit/(:any)/(:num)', 'Attendance::edit/$1/$2');
    $routes->post('attendance/update/(:any)/(:num)', 'Attendance::update/$1/$2');
    $routes->post('attendance/delete/(:any)/(:num)', 'Attendance::delete/$1/$2');

    // Holidays
    $routes->get('holidays',                     'Holidays::index');
    $routes->get('holidays/create',              'Holidays::create');
    $routes->post('holidays/store',              'Holidays::store');
    $routes->get('holidays/edit/(:num)',         'Holidays::edit/$1');
    $routes->post('holidays/update/(:num)',      'Holidays::update/$1');
    $routes->post('holidays/delete/(:num)',      'Holidays::delete/$1');

    // Reports
    $routes->get('reports',                      'Reports::index');
    $routes->get('reports/employee-detail',      'Reports::employeeDetail');
    $routes->get('reports/export-csv',           'Reports::exportCsv');

    // Map
    $routes->get('map',                          'MapView::index');

    // Settings
    $routes->get('settings',                     'Settings::index');
    $routes->post('settings',                    'Settings::update');
});

// REST API (used by mobile app — no session needed)
$routes->group('api', function ($routes) {
    // Auth
    $routes->post('auth/login',        'Api\AuthApi::login');
    $routes->post('auth/set-password', 'Api\AuthApi::setPassword');

    $routes->post('attendance/scan',     'Api\AttendanceApi::scan');
    $routes->get('attendance/today',     'Api\AttendanceApi::today');
    $routes->get('attendance/history',   'Api\AttendanceApi::history');

    $routes->get('employees',            'Api\EmployeeApi::index');
    $routes->get('employees/(:num)',     'Api\EmployeeApi::show/$1');
    $routes->post('employees',           'Api\EmployeeApi::create');
    $routes->put('employees/(:num)',     'Api\EmployeeApi::update/$1');
    $routes->delete('employees/(:num)', 'Api\EmployeeApi::delete/$1');

    $routes->get('qr-codes',            'Api\QRCodeApi::index');
    $routes->post('qr-codes',           'Api\QRCodeApi::create');
    $routes->put('qr-codes/(:num)',     'Api\QRCodeApi::update/$1');
    $routes->delete('qr-codes/(:num)', 'Api\QRCodeApi::delete/$1');
    $routes->get('qr/live-token/(:segment)', 'Api\QrLiveApi::token/$1');

    $routes->get('reports/daily',        'Api\ReportApi::daily');
    $routes->get('reports/monthly',      'Api\ReportApi::monthly');
    $routes->get('reports/export',       'Api\ReportApi::export');

    $routes->get('map/live',             'Api\MapApi::live');

    $routes->get('holidays',             'Api\HolidayApi::index');
});
