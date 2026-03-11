<?php defined('APP') or die('Access denied'); ?>
<?php if (!empty($adminlteLayout)): ?>
  </div><!-- /.content-wrapper -->

  <footer class="main-footer">
    <strong>&copy; 2026 Propiedad de IPTE Soluciones S.A. de C.V.</strong>
  </footer>

  <aside class="control-sidebar control-sidebar-dark"></aside>
</div><!-- ./wrapper -->

<!-- jQuery (AdminLTE dependency) -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<!-- Bootstrap 4 bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 3 -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
<!-- Toastr notifications -->
<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>

<?php else: ?>
  <!-- Footer (landing page) -->
  <footer class="py-3 mt-auto text-center"
          style="background-color:#171933; color:rgba(255,255,255,.7); font-size:.75rem;">
    &copy; 2026 Propiedad de IPTE Soluciones S.A. de C.V.
  </footer>

<!-- Bootstrap 4 bundle -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>

<?= $extraScripts ?? '' ?>

</body>
</html>
