        </main>
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> Все права защищены</p>
            <p class="footer__version">Версия <?= e(app_version()) ?></p>
        </footer>
        <?php if (!empty($useAppShell) || !empty($useCuratorShell)): ?>
            </div>
        </div>
        <?php require __DIR__ . '/app_bottom_nav.php'; ?>
        <?php endif; ?>
    </div>
    <script src="<?= e($basePath ?? '') ?>assets/js/app.js?v=20261114igo"></script>
</body>
</html>
