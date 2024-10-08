<?php

//redirect to the new login page
header("Location: /public/");
exit;

// Set the Content Security Policy header
header("Content-Security-Policy: default-src 'self' https://cdn.plaid.com; script-src 'self' https://cdn.plaid.com/link/v2/stable/link-initialize.js; frame-src 'self' https://cdn.plaid.com; connect-src 'self' https://production.plaid.com;");

// Set the Permissions Policy header
header("Permissions-Policy: fullscreen=(self 'https://cdn.plaid.com' 'https://cdn-testing.plaid.com' 'https://secure.plaid.com' 'https://secure-testing.plaid.com' 'https://verify.plaid.com' 'https://verify-production.plaid.com' 'https://verify-testing.plaid.com');");

if (!file_exists('/var/www/portal.twe.tech/includes/config/config.php')) {
    header("Location: setup.php");
    exit;
}

session_start();


require_once "/var/www/portal.twe.tech/includes/config/config.php";

// Check if $mysqli is a valid connection
if (!$mysqli) {
    header("Location: /");
    exit;
}

// Check if the application is configured for HTTPS-only access
if ($config_https_only && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') && (!isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https')) {
    echo "Login is restricted as ITFlow defaults to HTTPS-only for enhanced security. To login using HTTP, modify the config.php file by setting config_https_only to false. However, this is strongly discouraged, especially when accessing from potentially unsafe networks like the internet.";
    exit;
}

require_once "/var/www/portal.twe.tech/includes/functions/functions.php";

require_once "/var/www/portal.twe.tech/includes/rfc6238.php";



// IP & User Agent for logging
$ip = sanitizeInput(getIP());
$user_agent = sanitizeInput($_SERVER['HTTP_USER_AGENT']);

// Block brute force password attacks - check recent failed login attempts for this IP
//  Block access if more than 15 failed login attempts have happened in the last 10 minutes
$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(log_id) AS failed_login_count FROM logs WHERE log_ip = '$ip' AND log_type = 'Login' AND log_action = 'Failed' AND log_created_at > (NOW() - INTERVAL 10 MINUTE)"));
$failed_login_count = intval($row['failed_login_count']);

if ($failed_login_count >= 15) {

    // Logging
    mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Login', log_action = 'Blocked', log_description = '$ip was blocked access to login due to IP lockout', log_ip = '$ip', log_user_agent = '$user_agent'");

    // Inform user & quit processing page
    exit("<h2>$config_app_name</h2>Your IP address has been blocked due to repeated failed login attempts. Please try again later. <br><br>This action has been logged.");
}

// Query Settings for company
$sql_settings = mysqli_query($mysqli, "SELECT * FROM settings LEFT JOIN companies ON settings.company_id = companies.company_id WHERE settings.company_id = 1");
$row = mysqli_fetch_array($sql_settings);

// Company info
$company_name = $row['company_name'];
$company_logo = $row['company_logo'];
$config_start_page = nullable_htmlentities($row['config_start_page']);
$config_login_message = nullable_htmlentities($row['config_login_message']);

// Mail
$config_smtp_host = $row['config_smtp_host'];
$config_smtp_port = intval($row['config_smtp_port']);
$config_smtp_encryption = $row['config_smtp_encryption'];
$config_smtp_username = $row['config_smtp_username'];
$config_smtp_password = $row['config_smtp_password'];
$config_mail_from_email = sanitizeInput($row['config_mail_from_email']);
$config_mail_from_name = sanitizeInput($row['config_mail_from_name']);

// Client Portal Enabled
$config_client_portal_enable = intval($row['config_client_portal_enable']);

// Login key (if setup)
$config_login_key_required = $row['config_login_key_required'];
$config_login_key_secret = $row['config_login_key_secret'];

// Login key verification
//  If no/incorrect 'key' is supplied, send to client portal instead
if ($config_login_key_required) {
    if (!isset($_GET['key']) || $_GET['key'] !== $config_login_key_secret) {
        header("Location: portal");
        exit();
    }
}

// HTTP-Only cookies
ini_set("session.cookie_httponly", true);

// Tell client to only send cookie(s) over HTTPS
if ($config_https_only || !isset($config_https_only)) {
    ini_set("session.cookie_secure", true);
}

// Handle POST login request
if (isset($_POST['login'])) {

    // Sessions should start after the user has POSTed data
    session_start();

    // Passed login brute force check
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    $current_code = 0; // Default value
    if (isset($_POST['current_code'])) {
        $current_code = intval($_POST['current_code']);
    }

    $row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM users LEFT JOIN user_settings on users.user_id = user_settings.user_id WHERE user_email = '$email' AND user_archived_at IS NULL AND user_status = 1"));

    // Check password
    if ($row && password_verify($password, $row['user_password'])) {

        // User password correct (partial login)

        // Set temporary user variables
        $user_name = sanitizeInput($row['user_name']);
        $user_id = intval($row['user_id']);
        $user_email = sanitizeInput($row['user_email']);
        $token = sanitizeInput($row['user_token']);
        $force_mfa = intval($row['user_config_force_mfa']);
        $user_role = intval($row['user_role']);
        $user_encryption_ciphertext = $row['user_specific_encryption_ciphertext'];
        $user_extension_key = $row['user_extension_key'];
        if($force_mfa == 1 && $token == NULL) {
            $config_start_page = "user_security.php";
        }

        // Get remember tokens less than 2 days old
        $remember_tokens = mysqli_query($mysqli, "SELECT remember_token_token FROM remember_tokens WHERE remember_token_user_id = $user_id AND remember_token_created_at > (NOW() - INTERVAL 5 DAY)");

        $bypass_2fa = false;
        if (isset($_COOKIE['rememberme'])) {
            while ($row = mysqli_fetch_assoc($remember_tokens)) {
                if (hash_equals($row['remember_token_token'], $_COOKIE['rememberme'])) {
                    $bypass_2fa = true;
                    break;
                }
            }
        } elseif (empty($token) || TokenAuth6238::verify($token, $current_code)) {
            $bypass_2fa = true;
        }

        if ($bypass_2fa) {
            if (isset($_POST['remember_me'])) {
                $newRememberToken = bin2hex(random_bytes(64));
                setcookie('rememberme', $newRememberToken, time() + 86400*2, "/", null, true, true);
                $updateTokenQuery = "INSERT INTO remember_tokens (remember_token_user_id, remember_token_token) VALUES ($user_id, '$newRememberToken')";
                mysqli_query($mysqli, $updateTokenQuery);
            }

            // FULL LOGIN SUCCESS - 2FA not configured or was successful

            // Check this login isn't suspicious
            $sql_ip_prev_logins = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(log_id) AS ip_previous_logins FROM logs WHERE log_type = 'Login' AND log_action = 'Success' AND log_ip = '$ip' AND log_user_id = $user_id"));
            $ip_previous_logins = sanitizeInput($sql_ip_prev_logins['ip_previous_logins']);

            $sql_ua_prev_logins = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(log_id) AS ua_previous_logins FROM logs WHERE log_type = 'Login' AND log_action = 'Success' AND log_user_agent = '$user_agent' AND log_user_id = $user_id"));
            $ua_prev_logins = sanitizeInput($sql_ua_prev_logins['ua_previous_logins']);

            // Notify if both the user agent and IP are different
            if (!empty($config_smtp_host) && $ip_previous_logins == 0 && $ua_prev_logins == 0) {
                $subject = "$config_app_name new login for $user_name";
                $body = "Hi $user_name, <br><br>A recent successful login to your $config_app_name account was considered a little unusual. If this was you, you can safely ignore this email!<br><br>IP Address: $ip<br> User Agent: $user_agent <br><br>If you did not perform this login, your credentials may be compromised. <br><br>Thanks, <br>ITFlow";

                $data = [
                    [
                        'from' => $config_mail_from_email,
                        'from_name' => $config_mail_from_name,
                        'recipient' => $user_email,
                        'recipient_name' => $user_name,
                        'subject' => $subject,
                        'body' => $body
                    ]
                ];
                addToMailQueue($mysqli, $data);
            }


            // Determine whether 2FA was used (for logs)
            $extended_log = ''; // Default value
            if ($current_code !== 0) {
                $extended_log = 'with 2FA';
            }

            // Logging successful login
            mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Login', log_action = 'Success', log_description = '$user_name successfully logged in $extended_log', log_ip = '$ip', log_user_agent = '$user_agent', log_user_id = $user_id");

            // Session info
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $user_name;
            $_SESSION['user_role'] = $user_role;
            $_SESSION['csrf_token'] = randomString(156);
            $_SESSION['logged'] = true;
            $_SESSION['database'] = $database;

            // Setup encryption session key
            if (isset($user_encryption_ciphertext) && $user_role > 1) {
                $site_encryption_master_key = decryptUserSpecificKey($user_encryption_ciphertext, $password);
                generateUserSessionKey($site_encryption_master_key);

                // Setup extension - currently unused
                if (is_null($user_extension_key)) {
                    // Extension cookie
                    // Note: Browsers don't accept cookies with SameSite None if they are not HTTPS.
                    setcookie("user_extension_key", "$user_extension_key", ['path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'None']);

                    // Set PHP session in DB, so we can access the session encryption data (above)
                    $user_php_session = session_id();
                    mysqli_query($mysqli, "UPDATE users SET user_php_session = '$user_php_session' WHERE user_id = $user_id");
                }

            }

            if ($_GET['last_visited']) {
                header("Location: ".$_SERVER["REQUEST_SCHEME"] . "://" . $config_base_url . base64_decode($_GET['last_visited']) );
            } else {
                header("Location: $config_start_page");
            }

        } else {

            // MFA is configured and needs to be confirmed, or was unsuccessful

            // HTML code for the token input field
            $token_field = "
                    <div class='input-group mb-3'>
                        <input type='text' inputmode='numeric' pattern='[0-9]*' class='form-control' placeholder='Enter your 2FA code' name='current_code' required autofocus>
                        <div class='input-group-append'>
                          <div class='input-group-text'>
                            <span class='fas fa-key'></span>
                          </div>
                        </div>
                      </div>";

            // Log/notify if MFA was unsuccessful
            if ($current_code !== 0) {

                // Logging
                mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Login', log_action = '2FA Failed', log_description = '$user_name failed 2FA', log_ip = '$ip', log_user_agent = '$user_agent', log_user_id = $user_id");

                // Email the tech to advise their credentials may be compromised
                if (!empty($config_smtp_host)) {
                    $subject = "Important: $config_app_name failed 2FA login attempt for $user_name";
                    $body = "Hi $user_name, <br><br>A recent login to your $config_app_name account was unsuccessful due to an incorrect 2FA code. If you did not attempt this login, your credentials may be compromised. <br><br>Thanks, <br>ITFlow";
                    $data = [
                        [
                            'from' => $config_mail_from_email,
                            'from_name' => $config_mail_from_name,
                            'recipient' => $user_email,
                            'recipient_name' => $user_name,
                            'subject' => $subject,
                            'body' => $body
                        ]
                    ];
                    $mail = addToMailQueue($mysqli, $data);
                }

                // HTML feedback for incorrect 2FA code
                $response = "
                      <div class='alert alert-warning'>
                        Please Enter 2FA Code!
                        <button class='close' data-bs-dismiss='alert'>&times;</button>
                      </div>";
            }
        }

    } else {

        // Password incorrect or user doesn't exist - show generic error

        header("HTTP/1.1 401 Unauthorized");

        mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Login', log_action = 'Failed', log_description = 'Failed login attempt using $email', log_ip = '$ip', log_user_agent = '$user_agent'");

        $response = "
              <div class='alert alert-danger'>
                Incorrect username or password.
                <button class='close' data-bs-dismiss='alert'>&times;</button>
              </div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= nullable_htmlentities($company_name); ?> | Login</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="/includes/plugins/fontawesome-free/css/all.min.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="keywords" content="Bootstrap Theme, Freebies, Dashboard, MIT license">
    <meta name="description" content="Stream - Dashboard UI Kit">
    <meta name="author" content="htmlstream.com">

    <!-- Favicon -->
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <!-- Web Fonts -->
    <link href="//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">

    <!-- Components Vendor Styles -->
    <link rel="stylesheet" href="/includes/dist/vendor/font-awesome/css/all.min.css">

    <!-- Theme Styles -->
    <link rel="stylesheet" href="/includes/dist/css/theme.css">
</head>

<body class="hold-transition login-page">
		<main class="container-fluid w-100" role="main">
			<div class="row">
				<div class="col-lg-6 d-flex flex-column justify-content-center align-items-center bg-white mnh-100vh login-box">
					<a class="u-login-form py-3 mb-auto login-logo" href="index.html">
                        <img alt="<?=nullable_htmlentities($company_name)?> logo" height="110" width="380" class="img-fluid" src="<?= "/uploads/settings/$company_logo"; ?>">
					</a>

                    <?php if(!empty($config_login_message)){ ?>
                        <p class="login-box-msg px-0"><?= nl2br($config_login_message); ?></p>
                    <?php } ?>

                    <?php if (isset($response)) { ?>
                        <p><?= $response; ?></p>
                    <?php } ?>

					<div class="u-login-form">
						<form method="post">
							<div class="mb-3">
								<h1 class="h2">Employee Access</h1>
								<p class="small">Login with technician email address and password.</p>
							</div>

							<div class="form-group mb-4" <?php if (isset($token_field)) { echo "style='display:none;'"; } ?> >
								<label for="email">Technician Email</label>
                                <input type="text" class="form-control" placeholder="Agent Email" name="email" value="<?php if (isset($token_field)) { echo $email; }?>" required <?php if (!isset($token_field)) { echo "autofocus"; } ?> >
							</div>

							<div class="form-group mb-4" <?php if (isset($token_field)) { echo "style='display:none;'"; } ?> >
								<label for="password">Password</label>
                                <input type="password" class="form-control" placeholder="Agent Password" name="password" value="<?php if (isset($token_field)) { echo $password; } ?>" required>
							</div>

                            <?php if (isset($token_field)) { echo $token_field; ?>

                            <div class="form-group d-flex justify-content-between align-items-center mb-4">
                                <div class="custom-control custom-checkbox">
                                    <input id="remember_me" class="custom-control-input" name="remember_me" type="checkbox">
                                    <label class="custom-control-label" for="remember_me">Remember me</label>
                                </div>
                            </div>

                            <?php } ?>

							<button class="btn btn-label-primary btn-block" type="submit" name="login">Login</button>
						</form>

                        <?php if($config_client_portal_enable == 1){ ?>
                        <hr>
                            <h5 class="text-center">Looking for the <a href="/">Client Portal?</a></h5>
                        <?php } ?>
					</div>

					<div class="u-login-form text-muted py-3 mt-auto">
						<small><i class="far fa-question-circle mr-1"></i> If you are not able to sign in, please <a href="mailto:help@twe.tech">contact us</a>.</small>
					</div>
				</div>

				<div class="col-lg-6 d-none d-lg-flex flex-column align-items-center justify-content-center bg-light">
					<img class="img-fluid position-relative u-z-index-3 mx-5" src="/includes/dist/svg/mockups/mockup.svg" alt="Image description">

					<figure class="u-shape u-shape--top-right u-shape--position-5">
						<img src="/includes/dist/svg/shapes/shape-1.svg" alt="Image description">
					</figure>
					<figure class="u-shape u-shape--center-left u-shape--position-6">
						<img src="/includes/dist/svg/shapes/shape-2.svg" alt="Image description">
					</figure>
					<figure class="u-shape u-shape--center-right u-shape--position-7">
						<img src="/includes/dist/svg/shapes/shape-3.svg" alt="Image description">
					</figure>
					<figure class="u-shape u-shape--bottom-left u-shape--position-8">
						<img src="/includes/dist/svg/shapes/shape-4.svg" alt="Image description">
					</figure>
				</div>
			</div>
		</main>

        <!-- jQuery -->
        <script src="/includes/plugins/jquery/jquery.min.js"></script>

        <!-- Bootstrap 4 -->
        <script src="/includes/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

        <!-- Prevents resubmit on refresh or back -->
        <script src="/js/login_prevent_resubmit.js"></script>

    </body>
</html>