<?php

// Default Column Sortby Filter
$sort = "location_name";
$order = "ASC";

require_once "/var/www/portal.twe.tech/includes/inc_all.php";

$archived = 0;


//Rebuild URL

$sql = mysqli_query(
    $mysqli,
    "SELECT SQL_CALC_FOUND_ROWS * FROM locations 
    WHERE location_client_id = $client_id
    "
);

$num_rows = mysqli_fetch_row(mysqli_query($mysqli, "SELECT FOUND_ROWS()"));

?>

<div class="card">
    <div class="card-header py-2">
        <h3 class="card-title mt-2"><i class="fa fa-fw fa-map-marker-alt mr-2"></i>Locations</h3>
        <div class="card-tools">
            <div class="btn-group">
                <button type="button" class="btn btn-label-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" class="dropdown-item loadModalContentBtn" data-modal-file="client_location_add_modal.php?client_id=<?= $client_id; ?>">
                    <i class="fas fa-plus mr-2"></i>New Location
                </button>
                <button type="button" class="btn btn-label-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                <div class="dropdown-menu">
                    <a class="dropdown-item text-dark" href="#" data-bs-toggle="modal" data-bs-target="#importLocationModal">
                        <i class="fa fa-fw fa-upload mr-2"></i>Import
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-dark" href="#" data-bs-toggle="modal" data-bs-target="#exportLocationModal">
                        <i class="fa fa-fw fa-download mr-2"></i>Export
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form autocomplete="off">
            <input type="hidden" name="client_id" value="<?= $client_id; ?>">
            <input type="hidden" name="archived" value="<?= $archived; ?>">
            <div class="row">

                <div class="col-md-4">
                    <div class="input-group mb-3 mb-md-0">
                        <input type="search" class="form-control" name="q" value="<?php if (isset($q)) { echo stripslashes(nullable_htmlentities($q)); } ?>" placeholder="Search Locations">
                        <div class="input-group-append">
                            <button class="btn btn-dark"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="float-right">
                        <?php if($archived == 1){ ?>
                        <a href="?client_id=<?= $client_id; ?>&archived=0" class="btn btn-label-primary"><i class="fa fa-fw fa-archive mr-2"></i>Archived</a>
                        <?php } else { ?>
                        <a href="?client_id=<?= $client_id; ?>&archived=1" class="btn btn-default"><i class="fa fa-fw fa-archive mr-2"></i>Archived</a>
                        <?php } ?>
                    </div>
                </div>

            </div>
        </form>
        <hr>
        <div class="card-datatable table-responsive container-fluid  pt-0">               
            <table class="datatables-basic table border-top">
                <thead class="<?php if ($num_rows[0] == 0) { echo "d-none"; } ?>">
                <tr>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Hours</th>
                    <th class="text-center">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php

                while ($row = mysqli_fetch_array($sql)) {
                    $location_id = intval($row['location_id']);
                    $location_name = nullable_htmlentities($row['location_name']);
                    $location_description = nullable_htmlentities($row['location_description']);
                    $location_country = nullable_htmlentities($row['location_country']);
                    $location_address = nullable_htmlentities($row['location_address']);
                    $location_city = nullable_htmlentities($row['location_city']);
                    $location_state = nullable_htmlentities($row['location_state']);
                    $location_zip = nullable_htmlentities($row['location_zip']);
                    $location_phone = formatPhoneNumber($row['location_phone']);
                    if (empty($location_phone)) {
                        $location_phone_display = "-";
                    } else {
                        $location_phone_display = $location_phone;
                    }
                    $location_hours = nullable_htmlentities($row['location_hours']);
                    if (empty($location_hours)) {
                        $location_hours_display = "-";
                    } else {
                        $location_hours_display = $location_hours;
                    }
                    $location_photo = nullable_htmlentities($row['location_photo']);
                    $location_notes = nullable_htmlentities($row['location_notes']);
                    $location_created_at = nullable_htmlentities($row['location_created_at']);
                    $location_contact_id = intval($row['location_contact_id']);
                    $location_primary = intval($row['location_primary']);
                    if ( $location_primary == 1 ) {
                        $location_primary_display = "<small class='text-success'><i class='fa fa-fw fa-check'></i> Primary</small>";
                    } else {
                        $location_primary_display = "";
                    }

                    ?>
                    <tr>
                        <td>
                            <a class="text-dark" href="#" data-bs-toggle="modal" data-bs-target="#editLocationModal<?= $location_id; ?>">
                                <div class="media">
                                    <i class="fa fa-fw fa-2x fa-map-marker-alt mr-3"></i>
                                    <div class="media-body">
                                        <div <?php if($location_primary) { echo "class='text-bold'"; } ?>><?= $location_name; ?></div>
                                        <div><small class="text-secondary"><?= $location_description; ?></small></div>
                                        <div><?= $location_primary_display; ?></div>
                                    </div>
                                </div>
                            </a>
                        </td>
                        <td><a href="//maps.<?= $map_source; ?>.com?q=<?= "$location_address $location_zip"; ?>" target="_blank"><?= $location_address; ?><br><?= "$location_city $location_state $location_zip"; ?></a></td>
                        <td><?= $location_phone_display; ?></td>
                        <td><?= $location_hours_display; ?></td>
                        <td>
                            <div class="dropdown dropleft text-center">
                                <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="client_location_edit_modal.php?location_id=<?= $location_id; ?>">
                                        <i class="fas fa-fw fa-edit mr-2"></i>Edit
                                    </a>
                                    <?php if ($user_role == 3 && $location_primary == 0) { ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger confirm-link" href="/post.php?archive_location=<?= $location_id; ?>">
                                            <i class="fas fa-fw fa-archive mr-2"></i>Archive
                                        </a>
                                        <?php if ($config_destructive_deletes_enable) { ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger text-bold confirm-link" href="/post.php?delete_location=<?= $location_id; ?>">
                                            <i class="fas fa-fw fa-trash mr-2"></i>Delete
                                        </a>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </td>
                    </tr>

                <?php } ?>

                </tbody>
            </table>
        </div>

    </div>
</div>

<?php

require_once '/var/www/portal.twe.tech/includes/footer.php';

