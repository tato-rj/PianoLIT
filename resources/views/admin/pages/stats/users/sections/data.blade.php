    <div class="row"> 
        <div class="col-12 mb-4">
          @chart([
            'url' => route('admin.stats.users'),
            'chart' => 'line',
            'type' => 'signups',
            'title' => 'Sign ups',
            'subtitle' => 'Flow of users over time',
            'height' => '30vh',
            'select' => [
              'origin' => [
                ['label' => 'Any origin', 'value' => null],
                ['label' => 'iOS', 'value' => 'ios'],
                ['label' => 'Website', 'value' => 'web']
              ]
            ],
            'buttons' => [
              'type' => ['daily', 'monthly', 'yearly']
            ]
          ])
        </div>
    </div>
    <div class="row"> 
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.stats.users'),
            'chart' => 'pie',
            'type' => 'gender',
            'title' => 'Gender',
            'subtitle' => 'Users by gender',
            'height' => '30vh',
            'select' => [
              'origin' => [
                ['label' => 'Any origin', 'value' => null],
                ['label' => 'iOS', 'value' => 'ios'],
                ['label' => 'Website', 'value' => 'web']
              ]
            ]
          ])
        </div>
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.stats.users'),
            'chart' => 'pie',
            'type' => 'confirmed',
            'title' => 'Email status',
            'subtitle' => 'Users with confirmed emails',
            'height' => '30vh',
            'select' => [
              'origin' => [
                ['label' => 'Any origin', 'value' => null],
                ['label' => 'iOS', 'value' => 'ios'],
                ['label' => 'Website', 'value' => 'web']
              ]
            ]
          ])
        </div>
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.stats.users'),
            'chart' => 'pie',
            'type' => 'favorites',
            'title' => 'Favorites',
            'subtitle' => 'Users with favorited pieces',
            'height' => '30vh',
          ])
        </div>
    </div>