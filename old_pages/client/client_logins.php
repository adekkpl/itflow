<?php

// Default Column Sortby Filter
$sort = "login_name";
$order = "ASC";

require_once "/var/www/portal.twe.tech/includes/inc_all.php";


//Rebuild URL

$sql = mysqli_query(
    $mysqli,
    "SELECT SQL_CALC_FOUND_ROWS * FROM logins
    WHERE login_client_id = $client_id
    AND (login_name LIKE '%$q%' OR login_description LIKE '%$q%' OR login_uri LIKE '%$q%')
    ORDER BY login_important DESC, $sort $order"
);

$num_rows = mysqli_fetch_row(mysqli_query($mysqli, "SELECT FOUND_ROWS()"));

?>

<div class="card">
    <div class="card-header py-2">
        <h3 class="card-title mt-2"><i class="fa fa-fw fa-key mr-2"></i>Logins</h3>
        <div class="card-tools">
            <div class="btn-group">
                <button type="button" class="btn btn-label-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="client_login_add_modal.php?client_id=<?= $client_id; ?>">
                    <i class="fas fa-plus mr-2"></i>New Login
                </button>
                <button type="button" class="btn btn-label-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                <div class="dropdown-menu">
                    <a class="dropdown-item text-dark" href="#" data-bs-toggle="modal" data-bs-target="#importLoginModal">
                        <i class="fa fa-fw fa-upload mr-2"></i>Import
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-dark" href="#" data-bs-toggle="modal" data-bs-target="#exportLoginModal">
                        <i class="fa fa-fw fa-download mr-2"></i>Export
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form autocomplete="off">
            <input type="hidden" name="client_id" value="<?= $client_id; ?>">
            <div class="row">

                <div class="col-md-4">
                    <div class="input-group mb-3 mb-md-0">
                        <input type="search" class="form-control" name="q" value="<?php if (isset($q)) { echo stripslashes(nullable_htmlentities($q)); } ?>" placeholder="Search Logins">
                        <div class="input-group-append">
                            <button class="btn btn-dark"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="float-right">
                    </div>
                </div>

            </div>
        </form>
        <hr>
        <div class="card-datatable table-responsive container-fluid  pt-0">               
            <table class="datatables-basic table border-top">
                <thead class="text-dark <?php if ($num_rows[0] == 0) {
                                            echo "d-none";
                                        } ?>">
                    <tr>
                        <th><a class="text-secondary" href="?<?= $url_query_strings_sort; ?>&sort=login_name&order=<?= $disp; ?>">Name</a></th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>OTP</th>
                        <th><a class="text-secondary" href="?<?= $url_query_strings_sort; ?>&sort=login_uri&order=<?= $disp; ?>">URI</a></th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                    while ($row = mysqli_fetch_array($sql)) {
                        $login_id = intval($row['login_id']);
                        $login_name = nullable_htmlentities($row['login_name']);
                        $login_description = nullable_htmlentities($row['login_description']);
                        $login_uri = nullable_htmlentities($row['login_uri']);
                        if (empty($login_uri)) {
                            $login_uri_display = "-";
                        } else {
                            $login_uri_display = "$login_uri<button class='btn btn-sm clipboardjs' type='button' data-clipboard-text='$login_uri'><i class='far fa-copy text-secondary'></i></button>";
                        }
                        $login_uri_2 = nullable_htmlentities($row['login_uri_2']);
                        $login_username = nullable_htmlentities(decryptLoginEntry($row['login_username']));
                        if (empty($login_username)) {
                            $login_username_display = "-";
                        } else {
                            $login_username_display = "$login_username<button class='btn btn-sm clipboardjs' type='button' data-clipboard-text='$login_username'><i class='far fa-copy text-secondary'></i></button>";
                        }
                        $login_password = nullable_htmlentities(decryptLoginEntry($row['login_password']));
                        $login_otp_secret = nullable_htmlentities($row['login_otp_secret']);
                        $login_id_with_secret = '"' . $row['login_id'] . '","' . $row['login_otp_secret'] . '"';
                        if (empty($login_otp_secret)) {
                            $otp_display = "-";
                        } else {
                            $otp_display = "<span onmouseenter='showOTPViaLoginID($login_id)'><i class='far fa-clock'></i> <span id='otp_$login_id'><i>Hover..</i></span></span>";
                        }
                        $login_note = nullable_htmlentities($row['login_note']);
                        $login_important = intval($row['login_important']);
                        $login_contact_id = intval($row['login_contact_id']);
                        $login_vendor_id = intval($row['login_vendor_id']);
                        $login_asset_id = intval($row['login_asset_id']);
                        $login_software_id = intval($row['login_software_id']);

                    ?>
                        <tr class="<?php if (!empty($login_important)) { echo "text-bold"; } ?>">
                            <td>
                                <a class="text-dark" href="#" data-bs-toggle="modal" data-bs-target="#editLoginModal<?= $login_id; ?>">
                                    <div class="media">
                                        <i class="fa fa-fw fa-2x fa-key mr-3"></i>
                                        <div class="media-body">
                                            <div><?= $login_name; ?></div>
                                            <div><small class="text-secondary"><?= $login_description; ?></small></div>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td><?= $login_username_display; ?></td>
                            <td>
                                <button class="btn p-0" type="button" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-info" data-bs-placement="top" title="<?= $login_password; ?>"><i class="fas fa-2x fa-ellipsis-h text-secondary me-1"></i><i class="fas fa-2x fa-ellipsis-h text-secondary"></i></button><button class="btn btn-sm clipboardjs" type="button" data-clipboard-text="<?= $login_password; ?>"><i class="far fa-copy text-secondary"></i></button>
                            </td>
                            <td><?= $otp_display; ?></td>
                            <td><?= $login_uri_display; ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <?php if ($login_uri) { ?>
                                    <a href="<?= $login_uri; ?>" target="_blank" class="btn btn-default btn-sm"><i class="fa fa-fw fa-external-link-alt"></i></a>
                                    <?php } ?>
                                    <div class="dropdown dropleft text-center">
                                        <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editLoginModal<?= $login_id; ?>">
                                                <i class="fas fa-fw fa-edit mr-2"></i>Edit
                                            </a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#shareModal" onclick="populateShareModal(<?= "$client_id, 'Login', $login_id"; ?>)">
                                                <i class="fas fa-fw fa-share mr-2"></i>Share
                                            </a>
                                            <?php if ($user_role == 3) { ?>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger text-bold" href="/post.php?delete_login=<?= $login_id; ?>">
                                                    <i class="fas fa-fw fa-trash mr-2"></i>Delete
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                            </td>
                        </tr>

                    <?php

                        require "/var/www/portal.twe.tech/includes/modals/client_login_edit_modal.php";
                    }

                    ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include script to get TOTP code via the login ID -->
<script src="/includes/js/logins_show_otp_via_id.js"></script>



<!-- Include script to generate readable passwords for login entries -->
<script src="/includes/js/logins_generate_password.js"></script>

<script src="/includes/plugins/Show-Hide-Passwords-Bootstrap-4/bootstrap-show-password.min.js"></script>

<?php

require_once "/var/www/portal.twe.tech/includes/modals/client_login_add_modal.php";

require_once "/var/www/portal.twe.tech/includes/modals/share_modal.php";

require_once "/var/www/portal.twe.tech/includes/modals/client_login_import_modal.php";

require_once "/var/www/portal.twe.tech/includes/modals/client_login_export_modal.php";

require_once '/var/www/portal.twe.tech/includes/footer.php';
