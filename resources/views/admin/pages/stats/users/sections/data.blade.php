    <div class="row"> 
        <div class="col-12 p-3">
            <div class="border py-4 px-3">
                <div id="stats-signups" data-chart="line" data-url="{{route('admin.stats.users')}}" class="carousel carousel-fade">
                    <div class="d-flex flex-wrap justify-content-between mb-4">
                        <h4 class="text-center"><strong>Flow of users over time</strong></h4>
                        <div class="d-flex">
                            <div class="btn-group btn-group-sm mx-1">
                              <button data-parent="#stats-signups" name="type" value="daily" class="form-control-sm chart-btn btn btn-secondary" selected>Daily</button>
                              <button data-parent="#stats-signups" name="type" value="monthly" class="form-control-sm chart-btn btn btn-outline-secondary" style="border-left: 0; border-right: 0;">Monthly</button>
                              <button data-parent="#stats-signups" name="type" value="yearly" class="form-control-sm chart-btn btn btn-outline-secondary">Yearly</button>
                            </div>
                            <div class="form-group-sm mx-1">
                                <select class="chart-select form-control form-control-sm" data-parent="#stats-signups">
                                    <option name="origin" value="">Any origin</option>
                                    <option name="origin" value="ios">iOS</option>
                                    <option name="origin" value="web">Website</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div style="height: 40vh">
                        <canvas id="chart-signups" class="w-100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row"> 
        <div class="col-lg-4 col-md-4 col-12 p-3">
            <div class="border py-4 px-3">
                <div id="stats-gender" data-chart="pie" data-url="{{route('admin.stats.users')}}">
                    <div class="d-flex flex-wrap justify-content-between mb-2">
                        <h4 class="text-center m-0"><strong>Gender</strong></h4>
                        <div class="form-group-sm mx-1">
                            <select class="chart-select form-control form-control-sm" data-parent="#stats-gender">
                                <option name="origin" value="">Any origin</option>
                                <option name="origin" value="ios">iOS</option>
                                <option name="origin" value="web">Website</option>
                            </select>
                        </div>
                        <button class="chart-btn d-none" data-parent="#stats-signups" name="type" value="gender" selected></button>
                    </div>
                    <div>
                        <canvas id="chart-gender" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-12 p-3">
            <div class="border py-4 px-3">
                <div id="stats-confirmed" data-chart="pie" data-url="{{route('admin.stats.users')}}">
                    <div class="d-flex flex-wrap justify-content-between mb-2">
                        <h4 class="text-center"><strong>Email status</strong></h4>
                          <div class="form-group-sm mx-1">
                            <select class="chart-select form-control form-control-sm" data-parent="#stats-confirmed">
                                <option name="origin" value="">Any origin</option>
                                <option name="origin" value="ios">iOS</option>
                                <option name="origin" value="web">Website</option>
                            </select>
                        </div>
                        <button class="chart-btn d-none" data-parent="#stats-confirmed" name="type" value="confirmed" selected></button>
                    </div>
                    <div>
                        <canvas id="chart-confirmed" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-12 p-3">
            <div class="border py-4 px-3">
                <div id="stats-favorites" data-chart="pie" data-url="{{route('admin.stats.users')}}">
                    <div class="d-flex flex-wrap justify-content-between mb-2">
                        <h4 class="text-center"><strong>Favorites</strong></h4>
                    </div>
                    <button class="chart-btn d-none" data-parent="#stats-favorites" name="type" value="favorites" selected></button>
                    <div>
                        <canvas id="chart-favorites" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>