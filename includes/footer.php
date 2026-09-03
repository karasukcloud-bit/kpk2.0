        </main>
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> Все права защищены</p>
            <p class="footer__version">Версия <?= e(app_version()) ?></p>
        </footer>
    </div>
    <script src="<?= e($basePath ?? '') ?>assets/js/app.js?v=20260903c"></script>
</body>
</html>
