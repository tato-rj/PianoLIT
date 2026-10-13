        <div class="row g-0 mb-4" id="user-analytics-overview">
          <div class="col-lg-4 col-md-4 col-12 bg-primary text-white">
            <a class="link-none w-100 h-100 d-flex flex-center" href="{{$userAnalytics['all']['url']}}">
              <div class="text-center py-4">
                <div class="opacity-6">Total number of users</div>
                <h1 style="font-size: 3.6em;" class="my-2 mx-auto">{{number_format($userAnalytics['all']['total'])}}</h1>
                <div>
                  @if($userAnalytics['all']['counts'][0] == $userAnalytics['all']['counts'][1])
                  @icon('circle-alert')Same as last week
                  @elseif($userAnalytics['all']['counts'][0] > $userAnalytics['all']['counts'][1])
                  @icon('arrow-down')Down {{$userAnalytics['all']['counts'][0] - $userAnalytics['all']['counts'][1]}} from last week
                  @else
                  @icon('arrow-up')Up {{$userAnalytics['all']['counts'][1] - $userAnalytics['all']['counts'][0]}} from last week
                  @endif
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-8 col-md-8 col-12 d-flex flex-column alert-grey">
            @foreach($userAnalytics['platforms'] as $platform)
            <a class="link-none" href="{{$platform['url']}}">
              <div class="p-4 align-items-center d-md-flex flex-wrap justify-content-between {{$loop->iteration == 2 ? 'border-y' : null}}" style="flex: 1">
                <div class="me-3">@icon(($platform['icon']['fa_type'] ?? null) === 'b' ? 'brand-' . $platform['icon']['icon'] : $platform['icon']['icon']){{$platform['total']}} {{$platform['label']}} Users</div>
                <div class="">
                  @if($platform['counts'][0] == $platform['counts'][1])
                  <small class="text-warning text-nowrap">@icon('circle-alert')Same as last week</small>
                  @elseif($platform['counts'][0] > $platform['counts'][1])
                  <small class="text-red text-nowrap">@icon('arrow-down')Down {{$platform['counts'][0] - $platform['counts'][1]}} from last week</small>
                  @else
                  <small class="text-green text-nowrap">@icon('arrow-up')Up {{$platform['counts'][1] - $platform['counts'][0]}} from last week</small>
                  @endif
                </div>
              </div>
            </a>
            @endforeach
          </div>
        </div>