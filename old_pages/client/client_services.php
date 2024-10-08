<?php

// Default Column Sortby Filter
$sort = "service_name";
$order = "ASC";

require_once "/var/www/portal.twe.tech/includes/inc_all.php";


//Rebuild URL

// Overview SQL query
$sql = mysqli_query(
    $mysqli,
    "SELECT SQL_CALC_FOUND_ROWS * FROM services
    WHERE service_client_id = '$client_id'
    AND (service_name LIKE '%$q%' OR service_description LIKE '%$q%' OR service_category LIKE '%$q%')
    ORDER BY $sort $order"
);

$num_rows = mysqli_fetch_row(mysqli_query($mysqli, "SELECT FOUND_ROWS()"));

?>
    <div class="card">
        <div class="card-header py-2">
            <h3 class="card-title mt-2"><i class="fa fa-fw fa-stream mr-2"></i>Services</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-label-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="client_service_add_modal.php?client_id=<?= $client_id; ?>"><i class="fas fa-plus mr-2"></i>New Service</button>
            </div>
        </div>

        <div class="card-body">

            <form autocomplete="off">
                <input type="hidden" name="client_id" value="<?= $client_id; ?>">
                <div class="row">


                    <div class="col-md-8">
                        <div class="float-right">
                        </div>
                    </div>
                </div>
            </form>
            <hr>

            <div class="card-datatable table-responsive container-fluid  pt-0">                   
<table class="datatables-basic table border-top">
                    <thead class="<?php if ($num_rows[0] == 0) { echo "d-none"; } ?>">
                    <tr>
                        <th><a class="text-dark">Name</a></th>
                        <th><a class="text-dark">Category</a></th>
                        <th><a class="text-dark">Importance</a></th>
                        <th><a class="text-dark">Updated</a></th>
                        <th class="text-center">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    while ($row = mysqli_fetch_array($sql)) {
                        $service_id = intval($row['service_id']);
                        $service_name = nullable_htmlentities($row['service_name']);
                        $service_description = nullable_htmlentities($row['service_description']);
                        $service_category = nullable_htmlentities($row['service_category']);
                        $service_importance = nullable_htmlentities($row['service_importance']);
                        $service_backup = nullable_htmlentities($row['service_backup']);
                        $service_notes = nullable_htmlentities($row['service_notes']);
                        $service_created_at = nullable_htmlentities($row['service_created_at']);
                        $service_updated_at = nullable_htmlentities($row['service_updated_at']);
                        $service_review_due = nullable_htmlentities($row['service_review_due']);

                        // Service Importance
                        if ($service_importance == "High") {
                            $service_importance_display = "<span class='p-2 badge badge-danger'>$service_importance</span>";
                        } elseif ($service_importance == "Medium") {
                            $service_importance_display = "<span class='p-2 badge badge-warning'>$service_importance</span>";
                        } elseif ($service_importance == "Low") {
                            $service_importance_display = "<span class='p-2 badge badge-info'>$service_importance</span>";
                        } else {
                            $service_importance_display = "-";
                        }

                        ?>

                        <tr>
                            <!-- Name/Category/Updated/Importance from DB -->
                            <td>
                                <a class="text-dark" href="#" data-bs-toggle="modal" data-bs-target="#viewServiceModal<?= $service_id; ?>">
                                    <div class="media">
                                        <i class="fa fa-fw fa-2x fa-stream mr-3"></i>
                                        <div class="media-body">
                                            <div><?= $service_name; ?></div>
                                            <div><small class="text-secondary"><?= $service_description; ?></small></div>
                                        </div>
                                    </div>
                                </a>
                        
                            </td>
                            <td><?= $service_category ?></td>
                            <td><?= $service_importance ?></td>
                            <td><?= $service_updated_at ?></td>

                            <!-- Action -->
                            <td>
                                <div class="dropdown dropleft text-center">
                                    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editServiceModal<?= $service_id; ?>">
                                            <i class="fas fa-fw fa-edit mr-2"></i>Edit
                                        </a>
                                        <?php if ($user_role == 3) { ?>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger text-bold confirm-link" href="/post.php?delete_service=<?= $service_id; ?>">
                                                <i class="fas fa-fw fa-trash mr-2"></i>Delete
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <?php

                        // Associated Assets (and their logins/networks/locations)
                        $sql_assets = mysqli_query(
                            $mysqli,
                            "SELECT * FROM service_assets
                            LEFT JOIN assets ON service_assets.asset_id = assets.asset_id
                            LEFT JOIN logins ON service_assets.asset_id = logins.login_asset_id
                            LEFT JOIN networks ON assets.asset_network_id = networks.network_id
                            LEFT JOIN locations ON assets.asset_location_id = locations.location_id
                            WHERE service_id = $service_id"
                        );

                        // Associated logins
                        $sql_logins = mysqli_query(
                            $mysqli,
                            "SELECT * FROM service_logins
                            LEFT JOIN logins ON service_logins.login_id = logins.login_id
                            WHERE service_id = $service_id"
                        );

                        // Associated Domains
                        $sql_domains = mysqli_query(
                            $mysqli,
                            "SELECT * FROM service_domains
                            LEFT JOIN domains ON service_domains.domain_id = domains.domain_id
                            WHERE service_id = $service_id"
                        );
                        // Associated Certificates
                        $sql_certificates = mysqli_query(
                            $mysqli,
                            "SELECT * FROM service_certificates
                            LEFT JOIN certificates ON service_certificates.certificate_id = certificates.certificate_id
                            WHERE service_id = $service_id"
                        );

                        // Associated URLs ---- REMOVED for now
                        //$sql_urls = mysqli_query($mysqli, "SELECT * FROM service_urls
                        //WHERE service_id = '$service_id'");

                        // Associated Vendors
                        $sql_vendors = mysqli_query(
                            $mysqli,
                            "SELECT * FROM service_vendors
                            LEFT JOIN vendors ON service_vendors.vendor_id = vendors.vendor_id
                            WHERE service_id = $service_id"
                        );

                        // Associated Contacts
                        $sql_contacts = mysqli_query(
                            $mysqli,
                            "SELECT * FROM service_contacts
                            LEFT JOIN contacts ON service_contacts.contact_id = contacts.contact_id
                            WHERE service_id = $service_id"
                        );

                        // Associated Documents
                        $sql_docs = mysqli_query(
                            $mysqli,
                            "SELECT * FROM service_documents
                            LEFT JOIN documents ON service_documents.document_id = documents.document_id
                            WHERE service_id = $service_id"
                        );



                    }
                    ?>

                    </tbody>
                </table>
            </div>

        </div>
    </div>

<?php

require_once '/var/www/portal.twe.tech/includes/footer.php';

