<?php

// Default Column Sortby/Order Filter
$sort = "inventory_created_at";
$order = "DESC";

require_once "/var/www/portal.twe.tech/includes/inc_all.php";

//Rebuild URL

$sql = mysqli_query(
    $mysqli,
    "SELECT
    product_name,
    SUM(inventory_quantity) as total_inventory,
    inventory_product_id,
    GROUP_CONCAT(DISTINCT inventory_location_name SEPARATOR ', ') AS inventory_locations
    FROM inventory
        LEFT JOIN inventory_locations ON inventory_locations.inventory_location_id = inventory.inventory_location_id
        LEFT JOIN products ON inventory_product_id = product_id
        LEFT JOIN users on inventory_location_user_id = user_id
        LEFT JOIN vendors ON inventory_vendor_id = vendor_id
        GROUP BY inventory_product_id
        ");

$num_rows = mysqli_fetch_row(mysqli_query($mysqli, "SELECT FOUND_ROWS()"));

?>

    <div class="card">
        <div class="card-header py-2">
            <h3 class="card-title mt-2"><i class="fas fa-fw fa-box mr-2"></i>Inventory</h3>
        </div>

        <div class="card-body">
            <form class="mb-4" autocomplete="off">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <input type="search" class="form-control" name="q" value="<?php if (isset($q)) { echo stripslashes(nullable_htmlentities($q)); } ?>" placeholder="Search Inventory">
                            <div class="input-group-append">
                                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilter"><i class="fas fa-filter"></i></button>
                                <button class="btn btn-label-primary"><i class="fa fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <div class="btn-group float-right">
                            <a href="inventory_locations.php" class="btn btn-label-primary"><i class="fa fa-fw fa-map-marker-alt mr-2"></i>Locations</b></a>
                            <div class="dropdown ml-2" id="bulkActionButton" hidden>
                                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-fw fa-layer-group mr-2"></i>Bulk Action (<span id="selectedCount">0</span>)
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkEditCategoryModal">
                                        <i class="fas fa-fw fa-list mr-2"></i>Set Category
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkEditAccountModal">
                                        <i class="fas fa-fw fa-piggy-bank mr-2"></i>Set Account
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkEditClientModal">
                                        <i class="fas fa-fw fa-user mr-2"></i>Set Client
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <hr>
            <form id="bulkActions" action="/post.php" method="post">
                <div class="card-datatable table-responsive container-fluid  pt-0">                       
<table class="datatables-basic table border-top">
                        <thead class="text-dark <?php if ($num_rows[0] == 0) { echo "d-none"; } ?>">
                        <tr>
                            <th><a class="text-dark" href="?<?= $url_query_strings_sort; ?>&sort=inventory_date&order=<?= $disp; ?>">Product Name</a></th>
                            <th><a class="text-dark" href="?<?= $url_query_strings_sort; ?>&sort=vendor_name&order=<?= $disp; ?>">Quantity</a></th>
                            <th><a class="text-dark" href="?<?= $url_query_strings_sort; ?>&sort=category_name&order=<?= $disp; ?>">Locations</a></th>
                            <th class="text-center">Manage product</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php

                        while ($row = mysqli_fetch_array($sql)) {
                            $inventory_id = intval($row['inventory_product_id']);
                            $inventory_name = sanitizeInput($row['product_name']);
                            $inventory_quantity = floatval($row['total_inventory']);
                            $inventory_product_id = intval($row['inventory_product_id']);
                            $inventory_locations = sanitizeInput($row['inventory_locations']);
                            ?>

                            <tr>
                                <td><?= $inventory_name; ?></td>
                                <td><?= $inventory_quantity; ?></td>
                                <td><?= $inventory_locations; ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="inventory_manage.php?inventory_product_id=<?= $inventory_product_id; ?>" class="btn btn-label-primary btn-sm"><i class="fas fa-fw fa-edit"></i></a>
                                    </div>
                                </td>

                            <?php
                        }

                        ?>

                        </tbody>
                    </table>
                </div>

            </form>

        </div>
    </div>

<script src="/includes/js/bulk_actions.js"></script>

<?php

require_once '/var/www/portal.twe.tech/includes/footer.php';
