<script>
    window.app = <?php echo json_encode([
        'csrfToken' => csrf_token(),
        'url' => \Request::root(),
        'youtube_key' => env('YOUTUBE_V3_KEY'),
        'user' => null
    ]); ?>
</script>