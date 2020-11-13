<script>
    window.app = <?php echo json_encode([
        'csrfToken' => csrf_token(),
        'url' => \Request::root(),
        'user' => auth()->check() ? auth()->user() : null
    ]); ?>
</script>