<script>
    window.app = <?php echo json_encode([
        'csrfToken' => csrf_token(),
        'url' => \Request::root(),
        'routes' => ['blogImageUpload' => route('admin.posts.upload-image')],
        'user' => auth()->guard('admin')->user(),
        'user_model' => get_class(auth()->guard('admin')->user()),
        'user_id' => auth()->guard('admin')->user()->id,
    ]); ?>
</script>
