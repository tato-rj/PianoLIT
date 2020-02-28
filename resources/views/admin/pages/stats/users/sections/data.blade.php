    <div class="row"> 
        <div class="col-12 mb-4">
          @chart([
            'url' => route('admin.stats.users'),
            'chart' => 'line',
            'type' => 'signups',
            'title' => 'Sign ups',
            'subtitle' => 'Flow of users over time',
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
            'height' => 'auto',
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
            'subtitle' => 'Users who confirmed their emails',
            'height' => 'auto',
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
            'height' => 'auto',
          ])
        </div>
    </div>