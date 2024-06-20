<?php

// src/Model/Invoice.php

namespace Twetech\Nestogy\Model;

use PDO;

class Invoice {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getInvoices() {
        $stmt = $this->pdo->query("SELECT * FROM invoices");
        return $stmt->fetchAll();
    }

    public function getInvoice($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        return $stmt->fetch();
    }

    public function getInvoiceBalance($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT invoice_amount - COALESCE(SUM(payment_amount), 0) AS balance FROM invoices LEFT JOIN payments ON payments.payment_invoice_id = invoices.invoice_id WHERE invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        return $stmt->fetch();
    }
    
}