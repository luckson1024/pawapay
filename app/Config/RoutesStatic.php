<?php
/**
 * Static Routes for PawaPay Integration
 *
 * This file contains the routing configuration for the PawaPay integration
 * into the Modesy platform. All routes are defined here to avoid conflicts
 * with the core Modesy routing system.
 */

// PawaPay Payment Routes
$routes->post('cart/pawapay-payment-post', 'CartController::pawapayPaymentPost');
$routes->post('webhook/pawapay', 'CartController::pawapayWebhook');
$routes->get('cart/predict-operator', 'CartController::predictOperator');

// PawaPay Admin Routes (for future payout functionality)
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], function($routes) {
    $routes->get('pawapay-config', 'PawaPayController::config');
    $routes->post('pawapay-config', 'PawaPayController::updateConfig');
});

// PawaPay API Routes (for future API endpoints)
$routes->group('api', function($routes) {
    $routes->get('pawapay/mnos', 'API\PaymentController::getMobileOperators');
    $routes->get('pawapay/validate-phone/(:segment)', 'API\PaymentController::validatePhone/$1');
});
