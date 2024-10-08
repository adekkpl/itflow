<div class="card-header header-elements">
    <h3 class="me-2">
        <i class="bx bx-support"></i>
        Support Tickets
    </h3>
    <div class="card-header-elements">
        <span class="badge rounded-pill bg-label-secondary p-2">Total: <?=$total_tickets_open + $total_tickets_closed?></span> |
        <a href="<?= isset($client_id) ? "/old_pages/client/client_" : "/old_pages/" ?>tickets.php?status=Open&assigned=all<?= isset($client_id) ? "&client_id=$client_id" : "" ?>" class="badge rounded-pill bg-label-success p-2">Open: <?=$total_tickets_open?></a> |
        <a href="<?= isset($client_id) ? "/old_pages/client/client_" : "/old_pages/" ?>tickets.php?status=5&assigned=all<?= isset($client_id) ? "&client_id=$client_id" : "" ?>" class="badge rounded-pill bg-label-danger p-2">Closed: <?=$total_tickets_closed?></a>
    </div>
    <div class="card-header-elements ms-auto">
        <div class="btn-group">
            <div class="btn-group" role="group">
                <button class="btn btn-label-dark dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                    <?=$mobile ? "" : "My Tickets"?>
                    <i class="fa fa-fw fa-envelope m-2"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="?status=Open&assigned=<?= $user_id ?>">Active tickets (<?= $user_active_assigned_tickets ?>)</a>
                    <a class="dropdown-item " href="?status=5&assigned=<?= $user_id ?>">Closed tickets</a>
                </div>
            </div>
            <?php if (!isset($_GET['client_id'])) { ?>
                <a href="?assigned=unassigned" class="btn btn-label-danger">
                    <strong><?=$mobile ? "" : "Unassigned:"?> <?= " ".$total_tickets_unassigned; ?></strong>
                    <span class="tf-icons fa fa-fw fa-exclamation-triangle mr-2"></span>
                </a> 
            <?php } ?>
            <a href="<?=isset($_GET['client_id']) ? "/old_pages/client/client_" : '/old_pages/'?>recurring_tickets.php" class="btn btn-label-info">
            <strong><?=$mobile ? "" : "Recurring:"?> <?= $total_scheduled_tickets; ?> </strong>
                <span class="tf-icons fa fa-fw fa-redo-alt mr-2"></span>
            </a>
            <?php if ($user_role == 3) { ?>
                <a href="#!" class="btn btn-label-secondary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_add_modal.php">
                    <?=$mobile ? "Add Ticket" : ""?>
                    <i class="fa fa-fw fa-plus mr-2"></i>
                </a>
            <?php } ?>
        </div>

    </div>
</div>
