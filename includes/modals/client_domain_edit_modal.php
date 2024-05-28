<?php require_once "/var/www/portal.twe.tech/includes/inc_all_modal.php"; ?>
<div class="modal" id="editDomainModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-fw fa-globe mr-2"></i>Editing domain: <span class="text-bold" id="editDomainHeader"></span></h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="/post.php" method="post" autocomplete="off">

                <div class="modal-body bg-white">
                <input type="hidden" name="domain_id" value="" id="editDomainId">
                <input type="hidden" name="client_id" value="<?= $client_id; ?>">
                    <ul class="nav nav-pills  mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" role="tab" data-bs-toggle="tab" href="#pills-overview">Overview</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" role="tab" data-bs-toggle="tab" href="#pills-records">Records</a>
                        </li>
                    </ul>

                    <hr>

                    <div class="tab-content">

                        <div class="tab-pane fade show active" id="pills-overview">

                            <div class="form-group">
                                <label>Domain Name <strong class="text-danger">*</strong></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-fw fa-globe"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="editDomainName" name="name" placeholder="Domain name example.com" value="" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-fw fa-angle-right"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="editDomainDescription" name="description" placeholder="Short Description">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Domain Registrar</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-fw fa-server"></i></span>
                                    </div>
                                    <select class="form-control select2" id='select2' id="editDomainRegistrarId" name="registrar">
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Webhost</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-fw fa-server"></i></span>
                                    </div>
                                    <select class="form-control select2" id='select2' id="editDomainWebhostId" name="webhost">
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Expire Date</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-fw fa-calendar-times"></i></span>
                                    </div>
                                    <input type="date" class="form-control" id="editDomainExpire" name="expire" max="2999-12-31">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Notes</label>
                                <textarea class="form-control" id="editDomainNotes" name="notes" rows="3" placeholder="Enter some notes"></textarea>
                            </div>

                        </div>

                        <div class="tab-pane fade" role="tabpanel" id="pills-records">

                            <div class="form-group">
                                <label>Domain IP(s)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-fw fa-project-diagram"></i></span>
                                    </div>
                                    <textarea class="form-control" id="editDomainIP" name="domain_ip" rows="1" disabled></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Name Servers</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-fw fa-crown"></i></span>
                                    </div>
                                    <textarea class="form-control" id="editDomainNameServers" name="name_servers" rows="1" disabled></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>MX Records</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-fw fa-mail-bulk"></i></span>
                                    </div>
                                    <textarea class="form-control" id="editDomainMailServers" name="mail_servers" rows="1" disabled></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>TXT Records</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-fw fa-check-double"></i></span>
                                    </div>
                                    <textarea class="form-control" id="editDomainTxtRecords" name="txt_records" rows="1" disabled></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Raw WHOIS</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-fw fa-search-plus"></i></span>
                                    </div>
                                    <textarea class="form-control" id="editDomainRawWhois" name="raw_whois" rows="6" disabled></textarea>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
                <div class="modal-footer bg-white">
                    <button type="submit" name="edit_domain" class="btn btn-label-primary text-bold"></i>Save</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"></i>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
