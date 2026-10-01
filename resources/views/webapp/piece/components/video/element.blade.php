@video([
    'classes' => 'w-100',
    'id' => 'piece-video-'.$tutorial->id,
    'moments' => $tutorial->listeningMoments(),
    'previewSeconds' => $hasMediaAccess ? null : $previewSeconds,
    'url' => $tutorial->video_url])
