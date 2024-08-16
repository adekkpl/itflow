<?php

// Default Column Sortby Filter
$sort = "custom_field_label";
$order = "ASC";

require_once "/var/www/portal.twe.tech/includes/inc_all_settings.php";


if (isset($_GET['table'])) {
    $table = sanitizeInput($_GET['table']);
} else {
    $table = "client_assets";
}

//Rebuild URL

$sql = mysqli_query(
    $mysqli,
    "SELECT SQL_CALC_FOUND_ROWS * FROM custom_fields
    WHERE custom_field_label LIKE '%$q%'
    AND custom_field_table = '$table'
    ORDER BY $sort $order"
);

$num_rows = mysqli_fetch_row(mysqli_query($mysqli, "SELECT FOUND_ROWS()"));

?>


    <div class="card">
        <div class="card-header py-2">
            <h3 class="card-title mt-2"><i class="fa fa-fw fa-th-list mr-2"></i><?= nullable_htmlentities($table); ?> Fields</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#createCustomFieldModal"><i class="fas fa-plus mr-2"></i>Create</button>
            </div>
        </div>
        <div class="card-body">
            <form autocomplete="off">
                <input type="hidden" name="table" value="<?= nullable_htmlentities($table); ?>">
                <div class="row">
                    <div class="col-sm-4 mb-2">
                        <div class="input-group">
                            <input type="search" class="form-control" name="q" value="<?php if (isset($q)) { echo stripslashes(nullable_htmlentities($q)); } ?>" placeholder="Search">
                            <div class="input-group-append">
                                <button class="btn btn-label-primary"><i class="fa fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <div class="btn-group float-right">
                            <a href="?table=client_assets" class="btn <?php if ($table == 'client_assets') { echo 'btn-soft-primary'; } else { echo 'btn-default'; } ?>">Assets</a>
                            <a href="?table=clients" class="btn <?php if ($table == 'clients') { echo 'btn-soft-primary'; } else { echo 'btn-default'; } ?>">Clients</a>
                        </div>
                    </div>
                </div>
            </form>
            <hr>
            <div class="card-datatable table-responsive container-fluid  pt-0">                   
<table class="datatables-basic table border-top">
                    <thead class="text-dark <?php if ($num_rows[0] == 0) { echo "d-none"; } ?>">
                    <tr>
                        <th><a class="text-dark" href="?<?= $url_query_strings_sort; ?>&sort=custom_field_label&order=<?= $disp; ?>">Label</a></th>
                        <th><a class="text-dark" href="?<?= $url_query_strings_sort; ?>&sort=custom_field_type&order=<?= $disp; ?>">Type</a></th>
                        <th class="text-center">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    while ($row = mysqli_fetch_array($sql)) {
                        $custom_field_id = intval($row['custom_field_id']);
                        $custom_field_label = nullable_htmlentities($row['custom_field_label']);
                        $custom_field_type = nullable_htmlentities($row['custom_field_type']);
                        $custom_field_location = intval($row['custom_field_location']);
                        $custom_field_order = intval($row['custom_field_order']);
                        
                        ?>
                        <tr>
                            <td><a class="text-dark" href="#" data-bs-toggle="modal" data-bs-target="#editCustomFieldModal<?= $custom_field_id; ?>"><?= $custom_field_label; ?></a></td>
                            <td><?= $custom_field_type; ?></td>
                            <td>
                                <div class="dropdown dropleft text-center">
                                    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editCustomFieldModal<?= $custom_field_id; ?>">
                                            <i class="fas fa-fw fa-edit mr-2"></i>Edit
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger text-bold confirm-link" href="/post.php?delete_custom_field=<?= $custom_field_id; ?>">
                                            <i class="fas fa-fw fa-trash mr-2"></i>Delete
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <?php

                        //$colors_diff = array_diff($colors_array,$colors_used_array);

                        include "custom_field_edit_modal.php";


                    }

                    ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php
require_once "/var/www/portal.twe.tech/includes/modals/custom_field_create_modal.php";

require_once '/var/www/portal.twe.tech/includes/footer.php';
