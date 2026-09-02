</main>
</div>
<footer class="app-footer">
  <div class="container-fluid px-4 py-3 d-flex flex-wrap justify-content-between small">
    <span>&copy; <?= date('Y') ?> RESQZONE — Hazard Intelligence & Safe-Zone Analytics. Prototype build.</span>
    <span>Data shown is demo/prototype data and does not reflect real government records.</span>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.HZ_BASE = "<?= $base ?? '' ?>";</script>
<script src="<?= $base ?? '' ?>assets/js/main.js"></script>
</body>
</html>
