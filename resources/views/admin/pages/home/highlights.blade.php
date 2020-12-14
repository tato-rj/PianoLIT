        <div class="row no-gutters mb-4" id="user-stats-overview">
          <div class="col-lg-4 col-md-4 col-12 bg-primary text-white">
            <a class="link-none w-100 h-100 d-flex flex-center" href="{{$userStats['all']['url']}}">
              <div class="text-center py-4">
                <div class="opacity-6">Total number of users</div>
                <h1 style="font-size: 3.6em;" class="my-2 mx-auto">{{number_format($userStats['all']['counts'][1])}}</h1>
                <div>
                  @if($userStats['all']['counts'][0] == $userStats['all']['counts'][1])
                  @fa(['icon' => 'exclamation-circle'])Same as last week
                  @elseif($userStats['all']['counts'][0] > $userStats['all']['counts'][1])
                  @fa(['icon' => 'arrow-down'])Down {{$userStats['all']['counts'][0] - $userStats['all']['counts'][1]}} from last week
                  @else
                  @fa(['icon' => 'arrow-up'])Up {{$userStats['all']['counts'][1] - $userStats['all']['counts'][0]}} from last week
                  @endif
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-8 col-md-8 col-12 d-flex flex-column alert-grey">
            @foreach($userStats['platforms'] as $platform)
            <a class="link-none" href="{{$platform['url']}}">
              <div class="p-4 align-items-center d-md-flex flex-wrap justify-content-between {{$loop->iteration == 2 ? 'border-y' : null}}" style="flex: 1">
                <div class="mr-3">@fa($platform['icon']){{$platform['counts'][1]}} {{$platform['label']}} Users</div>
                <div class="">
                  @if($platform['counts'][0] == $platform['counts'][1])
                  <small class="text-warning text-nowrap">@fa(['icon' => 'exclamation-circle'])Same as last week</small>
                  @elseif($platform['counts'][0] > $platform['counts'][1])
                  <small class="text-red text-nowrap">@fa(['icon' => 'arrow-down'])Down {{$platform['counts'][0] - $platform['counts'][1]}} from last week</small>
                  @else
                  <small class="text-green text-nowrap">@fa(['icon' => 'arrow-up'])Up {{$platform['counts'][1] - $platform['counts'][0]}} from last week</small>
                  @endif
                </div>
              </div>
            </a>
            @endforeach
          </div>
        </div>