<?php

use TomorrowIdeas\Plaid\Plaid;

// Set working directory to the directory this cron script lives at.
chdir(dirname(__FILE__));

require_once "/var/www/portal.twe.tech/includes/tenant_db.php";

require_once "/var/www/portal.twe.tech/includes/config/config.php";

require_once "/var/www/portal.twe.tech/includes/functions/functions.php";


$sql_companies = mysqli_query($mysqli, "SELECT * FROM companies, settings WHERE companies.company_id = settings.company_id AND companies.company_id = 1");

$row = mysqli_fetch_array($sql_companies);

// Company Details
$company_name = sanitizeInput($row['company_name']);
$company_phone = sanitizeInput(formatPhoneNumber($row['company_phone']));
$company_email = sanitizeInput($row['company_email']);
$company_website = sanitizeInput($row['company_website']);
$company_city = sanitizeInput($row['company_city']);
$company_state = sanitizeInput($row['company_state']);
$company_country = sanitizeInput($row['company_country']);
$company_locale = sanitizeInput($row['company_locale']);
$company_currency = sanitizeInput($row['company_currency']);

// Company Settings
$config_enable_cron = intval($row['config_enable_cron']);
$config_cron_key = $row['config_cron_key'];
$config_invoice_overdue_reminders = $row['config_invoice_overdue_reminders'];
$config_invoice_prefix = sanitizeInput($row['config_invoice_prefix']);
$config_invoice_from_email = sanitizeInput($row['config_invoice_from_email']);
$config_invoice_from_name = sanitizeInput($row['config_invoice_from_name']);
$config_invoice_late_fee_enable = intval($row['config_invoice_late_fee_enable']);
$config_invoice_late_fee_percent = floatval($row['config_invoice_late_fee_percent']);
$config_timezone = sanitizeInput($row['config_timezone']);

// Mail Settings
$config_smtp_host = $row['config_smtp_host'];
$config_smtp_username = $row['config_smtp_username'];
$config_smtp_password = $row['config_smtp_password'];
$config_smtp_port = intval($row['config_smtp_port']);
$config_smtp_encryption = $row['config_smtp_encryption'];
$config_mail_from_email = sanitizeInput($row['config_mail_from_email']);
$config_mail_from_name = sanitizeInput($row['config_mail_from_name']);
$config_recurring_auto_send_invoice = intval($row['config_recurring_auto_send_invoice']);

// Tickets
$config_ticket_prefix = sanitizeInput($row['config_ticket_prefix']);
$config_ticket_from_name = sanitizeInput($row['config_ticket_from_name']);
$config_ticket_from_email = sanitizeInput($row['config_ticket_from_email']);
$config_ticket_client_general_notifications = intval($row['config_ticket_client_general_notifications']);
$config_ticket_autoclose = intval($row['config_ticket_autoclose']);
$config_ticket_autoclose_hours = intval($row['config_ticket_autoclose_hours']);
$config_ticket_new_ticket_notification_email = sanitizeInput($row['config_ticket_new_ticket_notification_email']);

// Get Config for Telemetry
$config_theme = $row['config_theme'];
$config_ticket_email_parse = intval($row['config_ticket_email_parse']);
$config_module_enable_itdoc = intval($row['config_module_enable_itdoc']);
$config_module_enable_ticketing = intval($row['config_module_enable_ticketing']);
$config_module_enable_accounting = intval($row['config_module_enable_accounting']);
$config_telemetry = intval($row['config_telemetry']);

// Alerts
$config_enable_alert_domain_expire = intval($row['config_enable_alert_domain_expire']);
$config_send_invoice_reminders = intval($row['config_send_invoice_reminders']);

// Set Currency Format
$currency_format = numfmt_create($company_locale, NumberFormatter::CURRENCY);

// Set Timezone
date_default_timezone_set($config_timezone);

$argv = $_SERVER['argv'];

// Check cron is enabled
if ($config_enable_cron == 0) {
    exit("Cron: is not enabled -- Quitting..");
}

// Check Cron Key
if ( $argv[1] !== $config_cron_key ) {
    exit("Cron Key invalid  -- Quitting..");
}

/*
 * ###############################################################################################################
 *  STARTUP ACTIONS
 * ###############################################################################################################
 */

//Logging
mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Started', log_description = 'Cron Started'");



/*
 * ###############################################################################################################
 *  CLEAN UP (OLD) DATA
 * ###############################################################################################################
 */

// Clean-up ticket views table used for collision detection
mysqli_query($mysqli, "TRUNCATE TABLE ticket_views");

// Clean-up shared items that have been used
mysqli_query($mysqli, "DELETE FROM shared_items WHERE item_views = item_view_limit");

// Clean-up shared items that have expired
mysqli_query($mysqli, "DELETE FROM shared_items WHERE item_expire_at < NOW()");

// Invalidate any password reset links
mysqli_query($mysqli, "UPDATE contacts SET contact_password_reset_token = NULL WHERE contact_archived_at IS NULL");

// Clean-up old dismissed notifications
mysqli_query($mysqli, "DELETE FROM notifications WHERE notification_dismissed_at < CURDATE() - INTERVAL 90 DAY");

// Clean-up mail queue
mysqli_query($mysqli, "DELETE FROM email_queue WHERE email_queued_at < CURDATE() - INTERVAL 90 DAY");

// Clean-up old remember me tokens (2 or more days old)
mysqli_query($mysqli, "DELETE FROM remember_tokens WHERE remember_token_created_at < CURDATE() - INTERVAL 2 DAY");

//Logging
//mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron cleaned up old data'");

/*
 * ###############################################################################################################
 *  ACTION DATA
 * ###############################################################################################################
 */

// GET NOTIFICATIONS

// DOMAINS EXPIRING

if($config_enable_alert_domain_expire == 1){

    $domainAlertArray = [1,7,14,30,90,120];

    foreach ($domainAlertArray as $day) {

        //Get Domains Expiring
        $sql = mysqli_query(
            $mysqli,
            "SELECT * FROM domains
            LEFT JOIN clients ON domain_client_id = client_id 
            WHERE domain_expire IS NOT NULL AND domain_expire = CURDATE() + INTERVAL $day DAY"
        );

        while ($row = mysqli_fetch_array($sql)) {
            $domain_id = intval($row['domain_id']);
            $domain_name = sanitizeInput($row['domain_name']);
            $domain_expire = sanitizeInput($row['domain_expire']);
            $client_id = intval($row['client_id']);
            $client_name = sanitizeInput($row['client_name']);

            mysqli_query($mysqli, "INSERT INTO notifications SET notification_type = 'Domain Expiring', notification = 'Domain $domain_name for $client_name will expire in $day Days on $domain_expire', notification_action = 'client_domains.php?client_id=$client_id', notification_client_id = $client_id");

        }

    }
    // Logging
    //mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron created notifications for domain expiring'");
}

// CERTIFICATES EXPIRING

$certificateAlertArray = [1,7,14,30,90,120];

foreach ($certificateAlertArray as $day) {

    //Get Certs Expiring
    $sql = mysqli_query(
        $mysqli,
        "SELECT * FROM certificates
        LEFT JOIN clients ON certificate_client_id = client_id 
        WHERE certificate_expire = CURDATE() + INTERVAL $day DAY"
    );

    while ($row = mysqli_fetch_array($sql)) {
        $certificate_id = intval($row['certificate_id']);
        $certificate_name = sanitizeInput($row['certificate_name']);
        $certificate_domain = sanitizeInput($row['certificate_domain']);
        $certificate_expire = sanitizeInput($row['certificate_expire']);
        $client_id = intval($row['client_id']);
        $client_name = sanitizeInput($row['client_name']);

        mysqli_query($mysqli, "INSERT INTO notifications SET notification_type = 'Certificate Expiring', notification = 'Certificate $certificate_name for $client_name will expire in $day Days on $certificate_expire', notification_action = 'client_certificates.php?client_id=$client_id', notification_client_id = $client_id");

    }

}
// Logging
//mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron created notifications for certificates expiring'");

// Asset Warranties Expiring

$warranty_alert_array = [1,7,14,30,90,120];

foreach ($warranty_alert_array as $day) {
    //Get Asset Warranty Expiring
    $sql = mysqli_query(
        $mysqli,
        "SELECT * FROM assets
        LEFT JOIN clients ON asset_client_id = client_id
        WHERE asset_warranty_expire = CURDATE() + INTERVAL $day DAY"
    );

    while ($row = mysqli_fetch_array($sql)) {
        $asset_id = intval($row['asset_id']);
        $asset_name = sanitizeInput($row['asset_name']);
        $asset_warranty_expire = sanitizeInput($row['asset_warranty_expire']);
        $client_id = intval($row['client_id']);
        $client_name = sanitizeInput($row['client_name']);

        mysqli_query($mysqli, "INSERT INTO notifications SET notification_type = 'Asset Warranty Expiring', notification = 'Asset $asset_name warranty for $client_name will expire in $day Days on $asset_warranty_expire', notification_action = 'client_assets.php?client_id=$client_id', notification_client_id = $client_id");

    }
}
// Logging
//mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron created notifications for asset warranties expiring'");

// Notify of New Tickets
// Get Ticket Pending Assignment
$sql_tickets_pending_assignment = mysqli_query($mysqli,"SELECT ticket_id FROM tickets
    WHERE ticket_status = 1"
);

$tickets_pending_assignment = mysqli_num_rows($sql_tickets_pending_assignment);

if($tickets_pending_assignment > 0){

    mysqli_query($mysqli, "INSERT INTO notifications SET notification_type = 'Pending Tickets', notification = 'There are $tickets_pending_assignment new tickets pending assignment', notification_action = 'tickets.php?status=New'");

    // Logging
    mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron created notifications for new tickets that are pending assignment'");
}

// Recurring (Scheduled) tickets

// Get recurring tickets for today
$sql_scheduled_tickets = mysqli_query($mysqli, "SELECT * FROM scheduled_tickets WHERE scheduled_ticket_next_run = CURDATE()");

if (mysqli_num_rows($sql_scheduled_tickets) > 0) {
    while ($row = mysqli_fetch_array($sql_scheduled_tickets)) {

        $schedule_id = intval($row['scheduled_ticket_id']);
        $subject = sanitizeInput($row['scheduled_ticket_subject']);
        $details = mysqli_real_escape_string($mysqli, $row['scheduled_ticket_details']);
        $priority = sanitizeInput($row['scheduled_ticket_priority']);
        $frequency = sanitizeInput(strtolower($row['scheduled_ticket_frequency']));
        $created_id = intval($row['scheduled_ticket_created_by']);
        $assigned_id = intval($row['scheduled_ticket_assigned_to']);
        $client_id = intval($row['scheduled_ticket_client_id']);
        $contact_id = intval($row['scheduled_ticket_contact_id']);
        $asset_id = intval($row['scheduled_ticket_asset_id']);

        $ticket_status = 1; // Default
        if ($assigned_id > 0) {
            $ticket_status = 2; // Set to open if we've auto-assigned an agent
        }

        // Assign this new ticket the next ticket number
        $ticket_number_sql = mysqli_fetch_array(mysqli_query($mysqli, "SELECT config_ticket_next_number FROM settings WHERE company_id = 1"));
        $ticket_number = intval($ticket_number_sql['config_ticket_next_number']);

        // Increment config_ticket_next_number by 1 (for the next ticket)
        $new_config_ticket_next_number = $ticket_number + 1;
        mysqli_query($mysqli, "UPDATE settings SET config_ticket_next_number = $new_config_ticket_next_number WHERE company_id = 1");

        // Raise the ticket
        mysqli_query($mysqli, "INSERT INTO tickets SET ticket_prefix = '$config_ticket_prefix', ticket_number = $ticket_number, ticket_subject = '$subject', ticket_details = '$details', ticket_priority = '$priority', ticket_status = '$ticket_status', ticket_created_by = $created_id, ticket_assigned_to = $assigned_id, ticket_contact_id = $contact_id, ticket_client_id = $client_id, ticket_asset_id = $asset_id");
        $id = mysqli_insert_id($mysqli);

        // Logging
        mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Ticket', log_action = 'Create', log_description = 'System created recurring scheduled $frequency ticket - $subject', log_client_id = $client_id, log_user_id = $created_id");

        // Notifications

        // Get client/contact/ticket details
        $sql = mysqli_query(
            $mysqli,
            "SELECT client_name, contact_name, contact_email, ticket_prefix, ticket_number, ticket_priority, ticket_subject, ticket_details FROM tickets
                LEFT JOIN clients ON ticket_client_id = client_id
                LEFT JOIN contacts ON ticket_contact_id = contact_id
                WHERE ticket_id = $id"
        );
        $row = mysqli_fetch_array($sql);

        $contact_name = sanitizeInput($row['contact_name']);
        $contact_email = sanitizeInput($row['contact_email']);
        $client_name = sanitizeInput($row['client_name']);
        $contact_name = sanitizeInput($row['contact_name']);
        $contact_email = sanitizeInput($row['contact_email']);
        $ticket_prefix = sanitizeInput($row['ticket_prefix']);
        $ticket_number = intval($row['ticket_number']);
        $ticket_priority = sanitizeInput($row['ticket_priority']);
        $ticket_subject = sanitizeInput($row['ticket_subject']);
        $ticket_details = mysqli_real_escape_string($mysqli, $row['ticket_details']);

        $data = [];

        // Notify client by email their ticket has been raised, if general notifications are turned on & there is a valid contact email
        if (!empty($config_smtp_host) && $config_ticket_client_general_notifications == 1 && filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {

            $email_subject = "Ticket created - [$ticket_prefix$ticket_number] - $ticket_subject (scheduled)";
            $email_body = "<i style=\'color: #808080\'>##- Please type your reply above this line -##</i><br><br>Hello $contact_name,<br><br>A ticket regarding \"$ticket_subject\" has been automatically created for you.<br><br>--------------------------------<br>$ticket_details--------------------------------<br><br>Ticket: $ticket_prefix$ticket_number<br>Subject: $ticket_subject<br>Status: Open<br>Portal: https://$config_base_url/portal/ticket.php?id=$id<br><br>--<br>$company_name - Support<br>$config_ticket_from_email<br>$company_phone";

            $email = [
                    'from' => $config_ticket_from_email,
                    'from_name' => $config_ticket_from_name,
                    'recipient' => $contact_email,
                    'recipient_name' => $contact_name,
                    'subject' => $email_subject,
                    'body' => $email_body
            ];

            $data[] = $email;

        }

        // Notify agent's via the DL address of the new ticket, if it's populated with a valid email
        if (filter_var($config_ticket_new_ticket_notification_email, FILTER_VALIDATE_EMAIL)) {

            $email_subject = "ITFlow - New Recurring Ticket - $client_name: $ticket_subject";
            $email_body = "Hello, <br><br>This is a notification that a recurring (scheduled) ticket has been raised in ITFlow. <br>Ticket: $ticket_prefix$ticket_number<br>Client: $client_name<br>Priority: $priority<br>Link: https://$config_base_url/ticket.php?ticket_id=$id <br><br>--------------------------------<br><br><b>$ticket_subject</b><br>$ticket_details";

            $email = [
                    'from' => $config_ticket_from_email,
                    'from_name' => $config_ticket_from_name,
                    'recipient' => $config_ticket_new_ticket_notification_email,
                    'recipient_name' => $config_ticket_from_name,
                    'subject' => $email_subject,
                    'body' => $email_body
            ];

            $data[] = $email;
        }

        // Add to the mail queue
        addToMailQueue($mysqli, $data);

        // Set the next run date
        if ($frequency == "weekly") {
            // Note: We seemingly have to initialize a new datetime for each loop to avoid stacking the dates
            $now = new DateTime();
            $next_run = date_add($now, date_interval_create_from_date_string('1 week'));
        } elseif ($frequency == "monthly") {
            $now = new DateTime();
            $next_run = date_add($now, date_interval_create_from_date_string('1 month'));
        } elseif ($frequency == "quarterly") {
            $now = new DateTime();
            $next_run = date_add($now, date_interval_create_from_date_string('3 months'));
        } elseif ($frequency == "biannually") {
            $now = new DateTime();
            $next_run = date_add($now, date_interval_create_from_date_string('6 months'));
        } elseif ($frequency == "annually") {
            $now = new DateTime();
            $next_run = date_add($now, date_interval_create_from_date_string('12 months'));
        }

        // Update the run date
        $next_run = $next_run->format('Y-m-d');
        $a = mysqli_query($mysqli, "UPDATE scheduled_tickets SET scheduled_ticket_next_run = '$next_run' WHERE scheduled_ticket_id = $schedule_id");

    }
}

// Logging
//mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron created sent out recurring tickets'");


// AUTO CLOSE TICKET - CLOSE
//  Automatically silently closes tickets 22 hrs after the last chase

// Check to make sure auto-close is enabled
if ($config_ticket_autoclose == 1) {
    $sql_tickets_to_chase = mysqli_query(
        $mysqli,
        "SELECT * FROM tickets 
        WHERE ticket_status = 4
        AND ticket_updated_at < NOW() - INTERVAL $config_ticket_autoclose_hours HOUR"
    );

    while ($row = mysqli_fetch_array($sql_tickets_to_chase)) {

        $ticket_id = $row['ticket_id'];
        $ticket_prefix = sanitizeInput($row['ticket_prefix']);
        $ticket_number = intval($row['ticket_number']);
        $ticket_subject = sanitizeInput($row['ticket_subject']);
        $ticket_status = sanitizeInput($row['ticket_status']);
        $ticket_assigned_to = sanitizeInput($row['ticket_assigned_to']);
        $client_id = intval($row['ticket_client_id']);

        mysqli_query($mysqli,"UPDATE tickets SET ticket_status = 5, ticket_closed_at = NOW(), ticket_closed_by = $ticket_assigned_to WHERE ticket_id = $ticket_id");

        //Logging
        mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Ticket', log_action = 'Closed', log_description = '$ticket_prefix$ticket_number auto closed', log_entity_id = $ticket_id");

    }


    // AUTO CLOSE TICKETS - CHASE
    //  Automatically sends a chaser email after approx 48 hrs/2 days
    $sql_tickets_to_chase = mysqli_query(
        $mysqli,
        "SELECT contact_name, contact_email, ticket_id, ticket_prefix, ticket_number, ticket_subject, ticket_status, ticket_client_id FROM tickets 
        LEFT JOIN clients ON ticket_client_id = client_id 
        LEFT JOIN contacts ON ticket_contact_id = contact_id
        WHERE ticket_status = 4
        AND ticket_updated_at < NOW() - INTERVAL 48 HOUR"
    );

    while ($row = mysqli_fetch_array($sql_tickets_to_chase)) {

        $contact_name = sanitizeInput($row['contact_name']);
        $contact_email = sanitizeInput($row['contact_email']);
        $ticket_id = intval($row['ticket_id']);
        $ticket_prefix = sanitizeInput($row['ticket_prefix']);
        $ticket_number = intval($row['ticket_number']);
        $ticket_subject = sanitizeInput($row['ticket_subject']);
        $ticket_status = sanitizeInput($row['ticket_status']);
        $client_id = intval($row['ticket_client_id']);

        $sql_ticket_reply = mysqli_query($mysqli, "SELECT ticket_reply FROM ticket_replies WHERE ticket_reply_type = 'Public' AND ticket_reply_ticket_id = $ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1");
        $ticket_reply_row = mysqli_fetch_array($sql_ticket_reply);
        $ticket_reply = $ticket_reply_row['ticket_reply'];

        $subject = "Ticket pending closure - [$ticket_prefix$ticket_number] - $ticket_subject";

        $body = "<i style=\'color: #808080\'>##- Please type your reply above this line -##</i><br><br>Hello, $contact_name<br><br>This is an automatic friendly reminder that your ticket regarding \"$ticket_subject\" will be closed, unless you respond.<br><br>--------------------------------<br>$ticket_reply--------------------------------<br><br>If your issue is resolved, you can ignore this email - the ticket will automatically close. If you need further assistance, please respond to this email.  <br><br>Ticket: $ticket_prefix$ticket_number<br>Subject: $ticket_subject<br>Status: $ticket_status<br>Portal: https://$config_base_url/portal/ticket.php?id=$ticket_id<br><br>--<br>$company_name - Support<br>$config_ticket_from_email<br>$company_phone";

        $data = [
            [
                'from' => $config_ticket_from_email,
                'from_name' => $config_ticket_from_name,
                'recipient' => $contact_email,
                'recipient_name' => $contact_name,
                'subject' => $subject,
                'body' => $body
            ]
        ];
        $mail = addToMailQueue($mysqli, $data);

        if ($mail !== true) {
            mysqli_query($mysqli,"INSERT INTO notifications SET notification_type = 'Mail', notification = 'Failed to send email to $contact_email'");
            mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Mail', log_action = 'Error', log_description = 'Failed to send email to $contact_email regarding $subject. $mail'");
        }

        mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Ticket Reply', log_action = 'Create', log_description = 'Auto close chaser email sent to $contact_email for ticket $ticket_prefix$ticket_number - $ticket_subject', log_client_id = $client_id");

    }
}

if ($config_send_invoice_reminders == 1) {

    // PAST DUE INVOICE Notifications
    //$invoiceAlertArray = [$config_invoice_overdue_reminders];
    $invoiceAlertArray = [30,60,90,120,150,180,210,240,270,300,330,360,390,420,450,480,510,540,570,590,620,650,680,710,740];


    foreach ($invoiceAlertArray as $day) {

        $sql = mysqli_query(
            $mysqli,
            "SELECT * FROM invoices
            LEFT JOIN clients ON invoice_client_id = client_id
            LEFT JOIN contacts ON clients.client_id = contacts.contact_client_id AND contact_primary = 1
            WHERE invoice_status != 'Draft'
            AND invoice_status != 'Paid'
            AND invoice_status != 'Cancelled'
            AND DATE_ADD(invoice_due, INTERVAL $day DAY) = CURDATE()
            ORDER BY invoice_number DESC"
        );

        while ($row = mysqli_fetch_array($sql)) {
            $invoice_id = intval($row['invoice_id']);
            $invoice_prefix = sanitizeInput($row['invoice_prefix']);
            $invoice_number = intval($row['invoice_number']);
            $invoice_status = sanitizeInput($row['invoice_status']);
            $invoice_date = sanitizeInput($row['invoice_date']);
            $invoice_due = sanitizeInput($row['invoice_due']);
            $invoice_url_key = sanitizeInput($row['invoice_url_key']);
            $invoice_amount = floatval($row['invoice_amount']);
            $invoice_currency_code = sanitizeInput($row['invoice_currency_code']);
            $client_id = intval($row['client_id']);
            $client_name = sanitizeInput($row['client_name']);
            $contact_name = sanitizeInput($row['contact_name']);
            $contact_email = sanitizeInput($row['contact_email']);
            $invoice_balance = getInvoiceBalance( $invoice_id);

            //Check for overpaymenPt
            $overpayment = $invoice_balance - $invoice_amount;


            // exit loop if overpayment is greater than 0
            if ($overpayment > 0) {
                continue;
            }

            // Late Charges

            if ($config_invoice_late_fee_enable == 1) {

                $todays_date = date('Y-m-d');
                $late_fee_amount = ($invoice_balance * $config_invoice_late_fee_percent) / 100;
                $new_invoice_amount = $invoice_amount + $late_fee_amount;

                mysqli_query($mysqli, "UPDATE invoices SET invoice_amount = $new_invoice_amount WHERE invoice_id = $invoice_id");

                //Insert Items into New Invoice
                mysqli_query($mysqli, "INSERT INTO invoice_items SET item_name = 'Late Fee', item_description = '$config_invoice_late_fee_percent% late fee applied on $todays_date', item_quantity = 1, item_price = $late_fee_amount, item_total = $late_fee_amount, item_order = 998, item_invoice_id = $invoice_id");

                mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Cron applied a late fee of $late_fee_amount', history_invoice_id = $invoice_id");

            }

            $subject = "$company_name Overdue Invoice $invoice_prefix$invoice_number";
            $body = "Hello $contact_name,<br><br>Our records indicate that we have not yet received payment for the invoice $invoice_prefix$invoice_number. We kindly request that you submit your payment as soon as possible. If you have any questions or concerns, please do not hesitate to contact us at $company_email or $company_phone.
                <br><br>
                Please review the invoice details mentioned below at your soonest convinience.<br><br>Invoice: $invoice_prefix$invoice_number<br>Issue Date: $invoice_date<br>Total: " . numfmt_format_currency($currency_format, $invoice_amount, $invoice_currency_code) . "<br>Due Date: $invoice_due<br>Over Due By: $day Days<br><br><br>To view your invoice, please click <a href=\'https://$config_base_url/portal/guest_view_invoice.php?invoice_id=$invoice_id&url_key=$invoice_url_key\'>here</a>.<br><br><br>--<br>$company_name - Billing<br>$config_invoice_from_email<br>$company_phone";

            $mail = addToMailQueue($mysqli, [
                [
                    'from' => $config_invoice_from_email,
                    'from_name' => $config_invoice_from_name,
                    'recipient' => $contact_email,
                    'recipient_name' => $contact_name,
                    'subject' => $subject,
                    'body' => $body
                ]
                ]);

            if ($mail === true) {
                mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Cron Emailed Overdue Invoice', history_invoice_id = $invoice_id");
            } else {
                mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Cron Failed to send Overdue Invoice', history_invoice_id = $invoice_id");

                mysqli_query($mysqli, "INSERT INTO notifications SET notification_type = 'Mail', notification = 'Failed to send email to $contact_email'");
                mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Mail', log_action = 'Error', log_description = 'Failed to send email to $contact_email regarding $subject. $mail'");
            }

        }

    }
}
// Logging
//mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron created notifications for past due invoices and sent out notifications to the primary contacts email'");

// Send Recurring Invoices that match todays date and are active

//Loop through all recurring that match today's date and is active
$sql_recurring = mysqli_query($mysqli, "SELECT * FROM recurring LEFT JOIN clients ON client_id = recurring_client_id WHERE recurring_next_date = CURDATE() AND recurring_status = 1");

while ($row = mysqli_fetch_array($sql_recurring)) {
    $recurring_id = intval($row['recurring_id']);
    $recurring_scope = sanitizeInput($row['recurring_scope']);
    $recurring_frequency = sanitizeInput($row['recurring_frequency']);
    $recurring_status = sanitizeInput($row['recurring_status']);
    $recurring_last_sent = sanitizeInput($row['recurring_last_sent']);
    $recurring_next_date = sanitizeInput($row['recurring_next_date']);
    $recurring_discount_amount = floatval($row['recurring_discount_amount']);
    $recurring_amount = floatval($row['recurring_amount']);
    $recurring_currency_code = sanitizeInput($row['recurring_currency_code']);
    $recurring_note = sanitizeInput($row['recurring_note']); //Escape SQL
    $category_id = intval($row['recurring_category_id']);
    $client_id = intval($row['recurring_client_id']);
    $client_name = sanitizeInput($row['client_name']); //Escape SQL just in case a name is like Safran's etc
    $client_net_terms = intval($row['client_net_terms']);


    //Get the last Invoice Number and add 1 for the new invoice number
    $sql_invoice_number = mysqli_query($mysqli, "SELECT * FROM settings WHERE company_id = 1");
    $row = mysqli_fetch_array($sql_invoice_number);
    $config_invoice_next_number = intval($row['config_invoice_next_number']);

    $new_invoice_number = $config_invoice_next_number;
    $new_config_invoice_next_number = $config_invoice_next_number + 1;
    mysqli_query($mysqli, "UPDATE settings SET config_invoice_next_number = $new_config_invoice_next_number WHERE company_id = 1");

    //Generate a unique URL key for clients to access
    $url_key = randomString(156);

    mysqli_query($mysqli, "INSERT INTO invoices SET invoice_prefix = '$config_invoice_prefix', invoice_number = $new_invoice_number, invoice_scope = '$recurring_scope', invoice_date = CURDATE(), invoice_due = DATE_ADD(CURDATE(), INTERVAL $client_net_terms day), invoice_discount_amount = $recurring_discount_amount, invoice_amount = $recurring_amount, invoice_currency_code = '$recurring_currency_code', invoice_note = '$recurring_note', invoice_category_id = $category_id, invoice_status = 'Sent', invoice_url_key = '$url_key', invoice_client_id = $client_id");

    $new_invoice_id = mysqli_insert_id($mysqli);

    //Copy Items from original recurring invoice to new invoice
    $sql_invoice_items = mysqli_query($mysqli, "SELECT * FROM invoice_items WHERE item_recurring_id = $recurring_id ORDER BY item_id ASC");

    while ($row = mysqli_fetch_array($sql_invoice_items)) {
        $item_id = intval($row['item_id']);
        $item_name = sanitizeInput($row['item_name']); //SQL Escape incase of ,
        $item_description = sanitizeInput($row['item_description']); //SQL Escape incase of ,
        $item_quantity = floatval($row['item_quantity']);
        $item_price = floatval($row['item_price']);
        $item_subtotal = floatval($row['item_subtotal']);
        $item_tax = floatval($row['item_tax']);
        $item_total = floatval($row['item_total']);
        $item_order = intval($row['item_order']);
        $tax_id = intval($row['item_tax_id']);
        $discount = floatval($row['item_discount']);
        $product_id = intval($row['item_product_id']);
        $category_id = intval($row['item_category_id']);

        //Insert Items into New Invoice
        mysqli_query($mysqli, "INSERT INTO invoice_items SET item_name = '$item_name', item_description = '$item_description', item_quantity = $item_quantity, item_price = $item_price, item_subtotal = $item_subtotal, item_tax = $item_tax, item_total = $item_total, item_order = $item_order, item_tax_id = $tax_id, item_invoice_id = $new_invoice_id, item_discount = $discount, item_product_id = $product_id, item_category_id = $category_id");

    }

    mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Invoice Generated from Recurring!', history_invoice_id = $new_invoice_id");

    //Update recurring dates

    mysqli_query($mysqli, "UPDATE recurring SET recurring_last_sent = CURDATE(), recurring_next_date = DATE_ADD(CURDATE(), INTERVAL 1 $recurring_frequency) WHERE recurring_id = $recurring_id");

    if ($config_recurring_auto_send_invoice == 1) {
        $sql = mysqli_query(
            $mysqli,
            "SELECT * FROM invoices
            LEFT JOIN clients ON invoice_client_id = client_id
            LEFT JOIN contacts ON clients.client_id = contacts.contact_client_id AND contact_primary = 1
            WHERE invoice_id = $new_invoice_id"
        );

        $row = mysqli_fetch_array($sql);
        $invoice_prefix = sanitizeInput($row['invoice_prefix']);
        $invoice_number = intval($row['invoice_number']);
        $invoice_scope = sanitizeInput($row['invoice_scope']);
        $invoice_date = sanitizeInput($row['invoice_date']);
        $invoice_due = sanitizeInput($row['invoice_due']);
        $invoice_amount = floatval($row['invoice_amount']);
        $invoice_url_key = sanitizeInput($row['invoice_url_key']);
        $client_id = intval($row['client_id']);
        $client_name = sanitizeInput($row['client_name']);
        $contact_name = sanitizeInput($row['contact_name']);
        $contact_email = sanitizeInput($row['contact_email']);

        $subject = "$company_name Invoice $invoice_prefix$invoice_number";
        $body = "Hello $contact_name,<br><br>An invoice regarding \"$invoice_scope\" has been generated. Please view the details below.<br><br>Invoice: $invoice_prefix$invoice_number<br>Issue Date: $invoice_date<br>Total: " . numfmt_format_currency($currency_format, $invoice_amount, $recurring_currency_code) . "<br>Due Date: $invoice_due<br><br><br>To view your invoice, please click <a href=\'https://$config_base_url/portal/guest_view_invoice.php?invoice_id=$new_invoice_id&url_key=$invoice_url_key\'>here</a>.<br><br><br>--<br>$company_name - Billing<br>$config_invoice_from_email<br>$company_phone";

        $mail = addToMailQueue($mysqli, [
            [
                'from' => $config_invoice_from_email,
                'from_name' => $config_invoice_from_name,
                'recipient' => $contact_email,
                'recipient_name' => $contact_name,
                'subject' => $subject,
                'body' => $body
            ]
        ]);

        if ($mail === true) {
            mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Cron Emailed Invoice!', history_invoice_id = $new_invoice_id");
            mysqli_query($mysqli, "UPDATE invoices SET invoice_status = 'Sent', invoice_client_id = $client_id WHERE invoice_id = $new_invoice_id");

        } else {
            mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Draft', history_description = 'Cron Failed to send Invoice!', history_invoice_id = $new_invoice_id");

            mysqli_query($mysqli, "INSERT INTO notifications SET notification_type = 'Mail', notification = 'Failed to send email to $contact_email'");
            mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Mail', log_action = 'Error', log_description = 'Failed to send email to $contact_email regarding $subject. $mail'");
        }

        // Send copies of the invoice to any additional billing contacts
        $sql_billing_contacts = mysqli_query($mysqli, "SELECT contact_name, contact_email FROM contacts
            WHERE contact_billing = 1
            AND contact_email != '$contact_email'
            AND contact_client_id = $client_id"
        );

        while ($billing_contact = mysqli_fetch_array($sql_billing_contacts)) {
            $billing_contact_name = sanitizeInput($billing_contact['contact_name']);
            $billing_contact_email = sanitizeInput($billing_contact['contact_email']);

            $data = [
                [
                    'from' => $config_invoice_from_email,
                    'from_name' => $config_invoice_from_name,
                    'recipient' => $billing_contact_email,
                    'recipient_name' => $billing_contact_name,
                    'subject' => $subject,
                    'body' => $body
                ]
            ];

            addToMailQueue($mysqli, $data);
        }

    } //End if Autosend is on
} //End Recurring Invoices Loop
// Logging
//mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron created invoices from recurring invoices and sent emails out'");

// Recurring Expenses
// Loop through all recurring expenses that match today's date and is active
$sql_recurring_expenses = mysqli_query($mysqli, "SELECT * FROM recurring_expenses WHERE recurring_expense_next_date = CURDATE() AND recurring_expense_status = 1");

while ($row = mysqli_fetch_array($sql_recurring_expenses)) {
    $recurring_expense_id = intval($row['recurring_expense_id']);
    $recurring_expense_frequency = intval($row['recurring_expense_frequency']);
    $recurring_expense_month = intval($row['recurring_expense_month']);
    $recurring_expense_day = intval($row['recurring_expense_day']);
    $recurring_expense_description = sanitizeInput($row['recurring_expense_description']);
    $recurring_expense_amount = floatval($row['recurring_expense_amount']);
    $recurring_expense_payment_method = sanitizeInput($row['recurring_expense_payment_method']);
    $recurring_expense_reference = sanitizeInput($row['recurring_expense_reference']);
    $recurring_expense_currency_code = sanitizeInput($row['recurring_expense_currency_code']);
    $recurring_expense_vendor_id = intval($row['recurring_expense_vendor_id']);
    $recurring_expense_category_id = intval($row['recurring_expense_category_id']);
    $recurring_expense_account_id = intval($row['recurring_expense_account_id']);
    $recurring_expense_client_id = intval($row['recurring_expense_client_id']);

    // Calculate next billing date based on frequency
    if ($recurring_expense_frequency == 1) { // Monthly
        $next_date_query = "DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
    } elseif ($recurring_expense_frequency == 2) { // Yearly
        $next_date_query = "DATE(CONCAT(YEAR(CURDATE()) + 1, '-', $recurring_expense_month, '-', $recurring_expense_day))";
    } else {
        // Handle unexpected frequency values. For now, just use current date.
        $next_date_query = "CURDATE()";
    }

    mysqli_query($mysqli,"INSERT INTO expenses SET expense_date = CURDATE(), expense_amount = $recurring_expense_amount, expense_currency_code = '$recurring_expense_currency_code', expense_account_id = $recurring_expense_account_id, expense_vendor_id = $recurring_expense_vendor_id, expense_client_id = $recurring_expense_client_id, expense_category_id = $recurring_expense_category_id, expense_description = '$recurring_expense_description', expense_reference = '$recurring_expense_reference'");

    $expense_id = mysqli_insert_id($mysqli);

    // Update recurring dates using calculated next billing date

    mysqli_query($mysqli, "UPDATE recurring_expenses SET recurring_expense_last_sent = CURDATE(), recurring_expense_next_date = $next_date_query WHERE recurring_expense_id = $recurring_expense_id");


} //End Recurring Invoices Loop
// Logging
//mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron created expenses from recurring expenses'");

// Collections

echo "Checking for clients that are past due and sending collections emails.\n";

// Loop through all clients and check their past due months

$sql_clients = mysqli_query($mysqli, "SELECT * FROM clients WHERE client_archived_at IS NULL AND client_net_terms > 0");

while ($row = mysqli_fetch_array($sql_clients)) {
    $client_id = intval($row['client_id']);
    $client_name = sanitizeInput($row['client_name']);
    $client_net_terms = intval($row['client_net_terms']);

    // Get the past due in months
    $months_past_due = getClientPastDueBalance($client_id);

    // Check if the past due is greater than client net terms and if so, send a collections email threatening termination
    // TODO: add setting to change when a client is considered past due (45 days, 60 days, etc.)
    if ($months_past_due >= ($client_net_terms/30)) {

        echo "Client $client_name is $months_past_due months past due. Sending collections email.\n";
        clientSendDisconnect($client_id);

        echo "Collections for $client_name finished.\n";

    } else {
        echo "Client $client_name is not past due: $months_past_due months past due.\n";
    }
}

// Plaid Bank Transaction Sync using TomorrowIdeas Plaid SDK
if ($config_plaid_enabled == 1) {

    // instantiate Plaid SDK
    require_once '/var/www/portal.twe.tech/vendor/autoload.php';

    $plaid = new Plaid(
        \getenv("PLAID_CLIENT_ID"),
        \getenv("PLAID_CLIENT_SECRET"),
        \getenv("PLAID_ENVIRONMENT")
    );

    $transactions = $plaid->transactions->sync(
        $plaid_access_token
    );
    // add transactions to database
    foreach ($transactions as $transaction) {
        $transaction_id = $transaction->transaction_id;
        $transaction_date = $transaction->date;
        $transaction_amount = $transaction->amount;
        $transaction_name = $transaction->name;
        $transaction_category = $transaction->category;
        $transaction_type = $transaction->type;
        $transaction_pending = $transaction->pending;
        $transaction_account_id = $transaction->account_id;
        $transaction_account_name = $transaction->account_name;
        $transaction_account_mask = $transaction->account_mask;
        $transaction_account_type = $transaction->account_type;
        $transaction_location = $transaction->location;
        $transaction_payment_meta = $transaction->payment_meta;
        $transaction_iso_currency_code = $transaction->iso_currency_code;
        $transaction_unofficial_currency_code = $transaction->unofficial_currency_code;

        // Check if transaction already exists
        $sql = mysqli_query($mysqli, "SELECT * FROM bank_transactions WHERE transaction_id = '$transaction_id'");
        if (mysqli_num_rows($sql) == 0) {
            // Insert transaction into database
            mysqli_query($mysqli, "INSERT INTO bank_transactions SET transaction_id = '$transaction_id', transaction_date = '$transaction_date', transaction_amount = '$transaction_amount', transaction_name = '$transaction_name', transaction_category = '$transaction_category', transaction_type = '$transaction_type', transaction_pending = '$transaction_pending', transaction_account_id = '$transaction_account_id', transaction_account_name = '$transaction_account_name', transaction_account_mask = '$transaction_account_mask', transaction_account_type = '$transaction_account_type', transaction_location = '$transaction_location', transaction_payment_meta = '$transaction_payment_meta', transaction_iso_currency_code = '$transaction_iso_currency_code', transaction_unofficial_currency_code = '$transaction_unofficial_currency_code'");
        }
    }

}
    

/*
 * ###############################################################################################################
 *  FINISH UP
 * ###############################################################################################################
 */

// Logging
mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Ended', log_description = 'Cron executed successfully'");
