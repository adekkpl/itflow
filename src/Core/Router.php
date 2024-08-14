<?php

namespace Twetech\Nestogy\Core;

use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Database;

class Router {
    private $routes = [];
    private $middlewares = [];
    private $defaultController = 'HomeController';
    private $defaultAction = 'index';
    private $pdo;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config.php';
        $database = new Database($config['db']);
        $this->pdo = $database->getConnection();
        $this->registerRoutes();
    }

    public function add($route, $controller, $action, $middlewares = [])
    {
        $this->routes[$route] = [
            'controller' => $controller ?: $this->defaultController,
            'action' => $action ?: $this->defaultAction,
            'middlewares' => $middlewares
        ];
    }

    public function registerRoutes()
    {
        // Client routes
        $this->add('clients', 'ClientController', 'index');
        $this->add('client', 'ClientController', 'show', ['client_id']);
        $this->add('contact', 'ClientController', 'showContacts', ['client_id']);
        $this->add('location', 'ClientController', 'showLocations', ['client_id']);

        // Support routes
        $this->add('tickets', 'SupportController', 'index', ['client_id', 'status', 'user_id']);
        $this->add('ticket', 'SupportController', 'show', ['ticket_id']);

        // Documentation routes
        $this->add('documentations', 'DocumentationController', 'index');
        $this->add('documentation', 'DocumentationController', 'show', ['documentation_type', 'client_id']);

        // Trip routes
        $this->add('trips', 'TripController', 'index', ['client_id']);
        $this->add('trip', 'TripController', 'show', ['trip_id']);

        // Accounting routes
        $this->add('invoices', 'AccountingController', 'showInvoices', ['client_id']);
        $this->add('invoice', 'AccountingController', 'showInvoice', ['invoice_id']);
        $this->add('subscriptions','AccountingController','showSubscriptions',['client_id']);
        $this->add('subscription','AccountingController','showSubscription',['subscription_id']);
        $this->add('payments', 'AccountingController', 'showPayments', ['client_id']);
        $this->add('payment', 'AccountingController', 'showPayment', ['payment_reference']);
        $this->add('make_payment', 'AccountingController', 'makePayment');
        $this->add('quotes', 'AccountingController', 'showQuotes', ['client_id']);
        $this->add('quote', 'AccountingController', 'showQuote', ['quote_id']);
        $this->add('contracts', 'AccountingController', 'showContracts', ['client_id']);
        $this->add('contract', 'AccountingController', 'showContract', ['contract_id']);
        $this->add('products', 'AccountingController', 'showProducts');
        $this->add('product', 'AccountingController', 'showProduct', ['product_id']);
        
        // Reports routes
        $this->add('report', 'ReportController', 'index', ['report']);

        // Administration routes
        $this->add('admin', 'AdministrationController', 'index', ['admin_page']);

        // Human Resources routes
        $this->add('hr', 'HumanResourcesController', 'index', ['hr_page', 'pay_period']);

        // Course route
        $this->add('learn', 'CourseController', 'index', ['course_id']);
    }

    public function dispatch()
    {
        // Get the page from the URL
        $page = $_GET['page'] ?? 'clients'; #TODO: Change this to the default page
        $route = $this->routes[$page] ?? null;

        // If the page is not found, handle the error
        if (!$route) {
            $this->handleNotFound();
            return;
        }

        // Get the controller and action from the route
        $controller = "Twetech\\Nestogy\\Controller\\" . $route['controller'];
        $action = $route['action'];
        $params = $this->getParams($route['middlewares']);

        // If the user is not logged in and the page is not the login page, redirect to the login page
        if (!Auth::check() && $page !== 'login') {
            header('Location: login.php');
            exit;
        }

        // If the controller and action exist, call them
        if (class_exists($controller) && method_exists($controller, $action)) {
            $controllerInstance = new $controller($this->pdo);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $postData = $this->sanitizePostData($_POST);
                call_user_func_array([$controllerInstance, $action], array_merge($params, [$postData]));
            } else {
                call_user_func_array([$controllerInstance, $action], $params);
            }
        } else {
            $this->handleNotFound();
        }
    }

    private function sanitizePostData($data)
    {
        $sanitizedData = [];
        foreach ($data as $key => $value) {
            $sanitizedData[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return $sanitizedData;
    }

    // Get the parameters from the URL
    private function getParams($middlewares)
    {
        $params = [];
        foreach ($middlewares as $param) {
            if (isset($_GET[$param])) {
                $params[] = htmlspecialchars($_GET[$param], ENT_QUOTES, 'UTF-8');
            } else {
                $params[] = null; // or handle missing parameters as needed
            }
        }
        return $params;
    }

    // Handle the error when the page is not found
    private function handleNotFound()
    {

        $view = new \Twetech\Nestogy\View\View();
        $messages = [
            "Well, this is awkward. The page you're looking for ran away with the circus. Try searching for something else or double-check that URL!",
            "Oh no! The page you're looking for is on vacation. Try searching for something else or double-check that URL!",
            "Oh dear! The page you're looking for must be taking a nap. Try searching for something else or double-check that URL!",
            "Oh snap! The page you're looking for is on a coffee break. Try searching for something else or double-check that URL!",
            "Oh my! The page you're looking for must be in a meeting. Try searching for something else or double-check that URL!",
            "Oh brother! The page you're looking for is at the gym. Try searching for something else or double-check that URL!",
            "Yee Yee, the page you're looking for is at the rodeo. Try searching for something else or double-check that URL!"
        ];
        $message = $messages[array_rand($messages)];
        $view->error([
            'title' => 'Oops! Page not found',
            'message' => $message
        ]);
    }
}