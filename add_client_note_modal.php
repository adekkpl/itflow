<div class="modal" id="addClientNoteModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-edit"></i> New Note</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="post.php" method="post" autocomplete="off">
        <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
        <div class="modal-body">  
          <div class="form-group">
            <label>Title</label>
            <input type="text" class="form-control" name="subject" required autofocus>
          </div>
          <div class="form-group">
            <label>Note</label>
            <textarea rows="8" class="form-control" name="note" id="addClientNote" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" name="add_client_note" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>