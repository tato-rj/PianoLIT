<script>
    window.app = <?php echo json_encode([
        'csrfToken' => csrf_token(),
        'url' => \Request::root(),
        'user' => auth()->guard('admin')->user(),
        'user_model' => get_class(auth()->guard('admin')->user()),
        'user_id' => auth()->guard('admin')->user()->id,
    ]); ?>
</script>