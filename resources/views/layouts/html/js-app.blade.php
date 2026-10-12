<script>
    window.app = <?php echo json_encode([
        'csrfToken' => csrf_token(),
        'url' => \Request::root(),
        'user' => auth('web')->check() ? auth('web')->user()->only(['id', 'first_name', 'last_name', 'full_name', 'email']) : null
    ]); ?>
</script>