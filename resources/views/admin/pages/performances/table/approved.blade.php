@if($item->video_url)
@toggle(['toggle' => $item->isApproved(), 'route' => route('admin.users.performances.approve', $item), 'autoToggle' => true])
@endif