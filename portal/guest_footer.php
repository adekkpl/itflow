        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
  </div>
  <!-- ./wrapper -->


  <footer class="content-footer footer bg-footer-theme">
    <div class="container-fluid pt-5 pb-4">
        <div class="row">
            <div class="row">
                <div class="col-12 col-sm-3 col-md-2 mb-4 mb-sm-4">
                    <h4 class="fw-bold mb-3"><a href="https://twe.tech" target="_blank" class="footer-text">ITFlow-NG </a></h4>        <span>Get ready for a better ERP.</span>
                    <div class="social-icon my-3">
                    <a href="javascript:void(0)" class="btn btn-icon btn-sm btn-facebook"><i class='bx bxl-facebook'></i></a>
                    <a href="javascript:void(0)" class="ms-2 btn btn-icon btn-sm btn-twitter"><i class='bx bxl-twitter'></i></a>
                    <a href="javascript:void(0)" class="ms-2 btn btn-icon btn-sm btn-linkedin"><i class='bx bxl-linkedin'></i></a>
                    </div>
                    <p class="pt-4">
                    <script>
                    document.write(new Date().getFullYear())
                    </script> © TWE Technologies
                    </p>
                </div>
            </div>
            <div class="row">
            </div>
        </div>
    </div>
</footer>

<!-- Overlay -->
<div class="layout-overlay layout-menu-toggle"></div>

<!-- Drag Target Area To SlideIn Menu On Small Screens -->
<div class="drag-target"></div>
</div>
<!-- / Layout wrapper -->




<!-- Core JS -->
<!-- build:js assets/vendor/js/core.js -->

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="/includes/assets/vendor/libs/popper/popper.js"></script>
<script src="/includes/assets/vendor/js/bootstrap.js"></script>
<script src="/includes/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="/includes/assets/vendor/libs/hammer/hammer.js"></script>

<script src="https://cdn.datatables.net/2.0.3/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.3/js/dataTables.bootstrap5.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.1/js/dataTables.responsive.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.1/js/responsive.bootstrap5.js"></script>

<script src="/includes/js/reformat_datetime.js"></script>

<script src="/includes/assets/vendor/js/menu.js"></script>

<!-- endbuild -->

<!-- Vendors JS -->

<script src="/includes/assets/vendor/libs/block-ui/block-ui.js"></script>
<script src="/includes/assets/vendor/libs/sortablejs/sortable.js"></script>
<script src="/includes/assets/vendor/libs/toastr/toastr.js"></script>
<script src="/includes/assets/vendor/libs/apex-charts/apexcharts.js"></script>

<!-- Main JS -->
<script src="/includes/assets/js/main.js"></script>

<script src="/includes/js/dynamic_modal_loading.js"></script>

<!-- Page JS -->

<script>
    tinymce.init({
        selector: 'textarea',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed linkchecker a11ychecker tinymcespellchecker permanentpen powerpaste advtable advcode editimage advtemplate ai mentions tinycomments tableofcontents footnotes mergetags autocorrect typography inlinecss markdown',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
        tinycomments_mode: 'embedded',
        tinycomments_author: 'Author name',
        mergetags_list: [
            { value: 'First.Name', title: 'First Name' },
            { value: 'Email', title: 'Email' },
        ],
        ai_request: (request, respondWith) => respondWith.string(() => Promise.reject("See docs to implement AI Assistant")),
    });
</script>

<script>

$(function () {
    $('.datatables-basic').DataTable({
        responsive: true,
        order: <?= $datatable_order ?>});
});

</script>

<script src="/includes/assets/js/cards-actions.js"></script>
</body>
</html>