<?php
// src/Controller/AccountingController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Client;

class AccountingController {
    private $pdo;
    private $view;
    private $auth;
    private $accounting;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->accounting = new Accounting($pdo);

        if (!$this->auth->check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    public function index() {
        //Redirect to /public/?page=home temporarily
        header('Location: /public/?page=home');
        exit;
    }

    public function showInvoices($client_id = false) {
        $auth = new Auth($this->pdo);

        if ($client_id) {
            // Check if user has access to the client
            if (!$auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
                // If user does not have access, display an error message
                $this->view->error([
                    'title' => 'Access Denied',
                    'message' => 'You do not have permission to view this client.'
                ]);
                return;
            }
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
        } else {
            $client_page = false;
        }

        $data['card']['title'] = 'Invoices';
        $data['table']['header_rows'] = ['Number', 'Client Name', 'Scope', 'Amount', 'Date', 'Status'];


        $invoices = $this->accounting->getInvoices($client_id);
        foreach ($invoices as $invoice) {
            // Get the client name
            $client_id = $invoice['invoice_client_id'];
            $client = new Client($this->pdo);
            $client_name = $client->getClient($client_id)['client_name'];
            $client_name_display = "<a class='btn btn-label-primary btn-sm' data-bs-toggle='tooltip' data-bs-placement='top' title='View Invoices for $client_name' href='?page=invoices&client_id=$client_id'>$client_name</a>";
            
            // Get the invoice number to display with a link to the invoice
            $invoice_number = $invoice['invoice_number'];
            $invoice_id = $invoice['invoice_id'];
            $invoice_prefix = $invoice['invoice_prefix'];
            $invoice_number_display = "<a class='btn btn-label-primary btn-sm' data-bs-toggle='tooltip' data-bs-placement='top' title='View $invoice_prefix $invoice_number' href='?page=invoice&invoice_id=$invoice_id'>$invoice_number</a>";

            // Check if the invoice is status sent and due date is in the past
            if ($invoice['invoice_status'] == 'Sent' && $invoice['invoice_due_date'] < date('Y-m-d')) {
                $invoice['invoice_status'] .= ' & Overdue';
            }

            $data['table']['body_rows'][] = [
                $invoice_number_display,
                $client_name_display,
                $invoice['invoice_scope'],
                $invoice['invoice_amount'],
                $invoice['invoice_date'],
                $invoice['invoice_status']
            ];
        }

        $this->view->render('simpleTable', $data, $client_page);
    }

    public function showInvoice($invoice_id) {
        $invoice = $this->accounting->getInvoice($invoice_id);
        $client_id = $invoice['invoice_client_id'];
        $invoice_tickets = $this->accounting->getTicketsByInvoice($invoice_id);
        $unbilled_tickets = $this->accounting->getUnbilledTickets($invoice_id);
        $client = new Client($this->pdo);
        $data = [
            'client' => $client,
            'client_header' => $client->getClientHeader($client_id)['client_header'],
            'invoice' => $invoice,
            'tickets' => $invoice_tickets,
            'unbilled_tickets' => $unbilled_tickets,
            'company' => $this->auth->getCompany()
        ];


        $this->view->render('invoice', $data, true);
    }

    public function showQuotes($client_id = false) {
        $auth = new Auth($this->pdo);

        if ($client_id) {
            // Check if user has access to the client
            if (!$auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
                // If user does not have access, display an error message
                $this->view->error([
                    'title' => 'Access Denied',
                    'message' => 'You do not have permission to view this client.'
                ]);
                return;
            }
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
        } else {
            $client_page = false;
        }

        $data['card']['title'] = 'Quotes';
        $data['table']['header_rows'] = ['Number', 'Client Name', 'Scope', 'Amount', 'Date', 'Status'];

        $quotes = $this->accounting->getQuotes($client_id);
        foreach ($quotes as $quote) {
            $client_id = $quote['quote_client_id'];
            $client = new Client($this->pdo);
            $client_name = $client->getClient($client_id)['client_name'];
            $client_name_display = "<a class='btn btn-label-primary btn-sm' data-bs-toggle='tooltip' data-bs-placement='top' title='View Quotes for $client_name' href='?page=quotes&client_id=$client_id'>$client_name</a>";
            $quote_number = $quote['quote_number'];
            $quote_id = $quote['quote_id'];
            $quote_prefix = $quote['quote_prefix'];
            $quote_number_display = "<a class='btn btn-label-primary btn-sm' data-bs-toggle='tooltip' data-bs-placement='top' title='View $quote_prefix $quote_number' href='?page=quote&quote_id=$quote_id'>$quote_number</a>";


            // Check if the quote is status sent and due expire is in the past
            if ($quote['quote_status'] == 'Sent' && $quote['quote_expire'] < date('Y-m-d')) {
                $quote['quote_status'] .= ' & Expired';
            }

            $data['table']['body_rows'][] = [
                $quote_number_display,
                $client_name_display,
                $quote['quote_scope'],
                $quote['quote_amount'],
                $quote['quote_date'],
                $quote['quote_status']
            ];
        }

        $this->view->render('simpleTable', $data, $client_page);
    }

    public function showQuote($quote_id) {
        $quote = $this->accounting->getQuote($quote_id);
        $client_id = $quote['quote_client_id'];
        $client = new Client($this->pdo);
        $data = [
            'client' => $client,
            'client_header' => $client->getClientHeader($client_id)['client_header'],
            'quote' => $quote
        ];
        $this->view->render('invoice', $data, true);
    }

    public function showSubscriptions($client_id = false) {
        $auth = new Auth($this->pdo);

        if ($client_id) {
            // Check if user has access to the client
            if (!$auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
                // If user does not have access, display an error message
                $this->view->error([
                    'title' => 'Access Denied',
                    'message' => 'You do not have permission to view this client.'
                ]);
                return;
            }
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
        } else {
            $client_page = false;
        }

        $data['card']['title'] = 'Subscriptions';
        $data['table']['header_rows'] = ['ID', 'Client', 'Product', 'Quantity', 'Updated'];

        $subscriptions = $this->accounting->getSubscriptions($client_id);
        foreach ($subscriptions as $subscription) {
            $client = new Client($this->pdo);
            $client_name = $client->getClient($subscription['subscription_client_id'])['client_name'];
            
            $product_name = $this->accounting->getProduct($subscription['subscription_product_id'])['product_name'];
            $data['table']['body_rows'][] = [
                $subscription['subscription_id'],
                $client_name,
                $product_name,
                $subscription['subscription_product_quantity'],
                $subscription['subscription_updated']
            ];
        }
        $data['action'] = [
            'title' => 'Add Subscription',
            'button' => 'Add',
            'modal' => 'subscription_add_modal.php?client_id=' . $client_id
        ];
        $this->view->render('simpleTable', $data, $client_page);
    }
    public function showSubscription($subscription_id) {
        $subscription = $this->accounting->getSubscription($subscription_id);
        $this->view->render('simpleTable', $subscription, true);
    }
}