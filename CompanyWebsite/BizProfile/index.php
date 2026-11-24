<?php

require_once __DIR__ . '/includes/bootstrap.php';

use BizProfile\Core\Router;
use BizProfile\Core\Request;
use BizProfile\Core\Response;
use BizProfile\Core\InstallationStateService;
use BizProfile\Core\SetupGate;

$request = new Request();
$router = new Router($request);

// --- M1 Setup Routes ---
$router->get('/setup', 'SetupController@index');
$router->get('/setup/identify', 'SetupController@identify');
$router->post('/setup/identify', 'SetupController@identify');
$router->get('/setup/otp/send', 'VerificationController@sendOtp');
$router->post('/setup/otp/send', 'VerificationController@sendOtp');
$router->get('/setup/otp/verify', 'VerificationController@verifyOtp');
$router->post('/setup/otp/verify', 'VerificationController@verifyOtp');
$router->get('/setup/resume', 'SetupController@resume');

// --- M1 Setup Gate (runs before dispatch) ---
$stateService = new InstallationStateService();
$gate = new SetupGate($stateService);

$uri = parse_url($request->getUri(), PHP_URL_PATH);
$method = $request->getMethod();
$isOwnerAuthenticated = !empty($_SESSION['owner_authenticated'] ?? false) && !empty($_SESSION['user_id'] ?? null);

$gateResult = $gate->evaluate($uri, $method, $isOwnerAuthenticated);

if ($gateResult['halt'] ?? false) {
    http_response_code($gateResult['code'] ?? 200);
    $controllerFile = BASE_PATH . '/admin/setup/' . ($gateResult['view'] ?? 'setup/pending') . '.php';
    if (file_exists($controllerFile)) {
        extract($gateResult['data'] ?? []);
        include $controllerFile;
    } else {
        echo 'Setup required.';
    }
    exit;
}

// --- Existing routes ---
$router->get('/', 'HomeController@index');
$router->get('/about', 'PageController@about');
$router->get('/services', 'PageController@services');
$router->get('/products', 'ProductController@index');
$router->get('/products/{slug}', 'ProductController@show');
$router->get('/product-releases', 'NewsController@releases');
$router->get('/clients', 'PageController@clients');
$router->get('/gallery', 'GalleryController@index');
$router->get('/careers', 'CareerController@index');
$router->get('/testimonials', 'PageController@testimonials');
$router->get('/blog', 'NewsController@index');
$router->get('/blog/{slug}', 'NewsController@show');
$router->get('/contact', 'ContactController@index');
$router->post('/contact', 'ContactController@store');
$router->get('/privacy-policy', 'PageController@privacy');
$router->get('/terms', 'PageController@terms');
$router->get('/sitemap.xml', 'SitemapController@index');

$router->get('/admin/login', 'AuthController@login');
$router->post('/admin/login', 'AuthController@authenticate');
$router->get('/admin/logout', 'AuthController@logout');
$router->get('/admin/dashboard', 'AdminController@dashboard');
$router->get('/admin/profile', 'AdminController@profile');
$router->post('/admin/profile', 'AdminController@updateProfile');
$router->post('/admin/profile/password', 'AdminController@changePassword');
$router->get('/admin/settings', 'AdminController@settings');
$router->post('/admin/settings', 'AdminController@updateSettings');
$router->get('/admin/logs', 'AdminController@logs');
$router->get('/admin/activity-logs', 'AdminController@activityLogs');

// Admin: products & services
$router->get('/admin/products', 'ProductServiceController@index');
$router->get('/admin/products/create', 'ProductServiceController@create');
$router->post('/admin/products', 'ProductServiceController@store');
$router->get('/admin/products/edit/{slug}', 'ProductServiceController@edit');
$router->post('/admin/products/update/{slug}', 'ProductServiceController@update');
$router->get('/admin/products/delete/{slug}', 'ProductServiceController@delete');

// Admin: product releases
$router->get('/admin/product-releases', 'ProductReleaseController@index');
$router->get('/admin/product-releases/create', 'ProductReleaseController@create');
$router->post('/admin/product-releases', 'ProductReleaseController@store');
$router->get('/admin/product-releases/edit/{slug}', 'ProductReleaseController@edit');
$router->post('/admin/product-releases/update/{slug}', 'ProductReleaseController@update');
$router->get('/admin/product-releases/delete/{slug}', 'ProductReleaseController@delete');
$router->get('/product-releases/download/{id}', 'ProductReleaseController@download');

// Public: demo request + product/service listing
$router->post('/demo-request', 'ProductServiceController@demoRequest');
$router->get('/products-services', 'ProductServiceController@index');

// Theme selector (web interface)
$router->get('/admin/theme', 'ThemeController@index');
$router->post('/admin/theme', 'ThemeController@select');

// Questionnaire routes (M2)
$router->get('/questionnaire', 'QuestionnaireController@index');
$router->get('/questionnaire/{slug}', 'QuestionnaireController@show');
$router->post('/questionnaire/{slug}/submit', 'QuestionnaireController@submit');
$router->get('/admin/questionnaires', 'QuestionnaireController@adminList');
$router->get('/admin/questionnaires/create', 'QuestionnaireController@adminCreate');
$router->post('/admin/questionnaires', 'QuestionnaireController@adminStore');
$router->get('/admin/questionnaires/{id:\d+}/responses', 'QuestionnaireController@adminResponses');

$router->get('/api/v1/products', 'Api\ProductController@index');
$router->get('/api/v1/products/{id}', 'Api\ProductController@show');
$router->get('/api/v1/services', 'Api\ServicesController@index');
$router->get('/api/v1/services/{id}', 'Api\ServicesController@show');
$router->get('/api/v1/news', 'Api\NewsController@index');
$router->get('/api/v1/testimonials', 'Api\TestimonialsController@index');
$router->get('/api/v1/testimonials/{id}', 'Api\TestimonialsController@show');
$router->get('/api/v1/gallery', 'Api\GalleryController@index');
$router->get('/api/v1/careers', 'Api\CareerController@index');
$router->post('/api/v1/contact', 'Api\ContactController@store');
$router->post('/api/v1/newsletter', 'Api\NewsletterController@subscribe');
$router->get('/api/v1/newsletter/unsubscribe/{email}', 'Api\NewsletterController@unsubscribe');
$router->get('/api/v1/search', 'Api\SearchController@search');
$router->get('/api/v1/docs', 'Api\DocsController@index');

$router->dispatch();

$response = new Response();
$response->send();