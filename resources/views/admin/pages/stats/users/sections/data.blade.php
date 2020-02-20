    <div class="row"> 
        <div class="col-12 p-3">
            <div class="border py-4 px-3">
                <div id="stats-signups" class="carousel carousel-fade">
                    <div class="d-flex justify-content-between mb-4">
                        <h4 class="text-center"><strong>Flow of users over time</strong></h4>
                        <div class="d-flex">
                            <div class="select-btn-group btn-group btn-group-sm mx-1">
                              <button data-model="users" data-type="daily" class="form-control-sm btn btn-secondary" selected>Daily</button>
                              <button data-model="users" data-type="monthly" class="form-control-sm btn btn-outline-secondary" style="border-left: 0; border-right: 0;">Monthly</button>
                              <button data-model="users" data-type="yearly" class="form-control-sm btn btn-outline-secondary">Yearly</button>
                            </div>
                            <div class="form-group-sm mx-1">
                                <select class="chart-select form-control form-control-sm" data-chart="line" data-parent="#stats-signups" name="origin">
                                    <option value="">Any origin</option>
                                    <option value="ios">iOS</option>
                                    <option value="web">Website</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div>
                        <canvas id="chart-signups" data-model="users" data-type="daily" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row"> 
        <div class="col-4 p-3">
            <div class="border py-4 px-3">
                <div id="stats-gender">
                    <div class="d-flex justify-content-between mb-2">
                        <h4 class="text-center m-0"><strong>Gender</strong></h4>
                        <div class="form-group-sm mx-1">
                            <select class="chart-select form-control form-control-sm" data-chart="pie" data-parent="#stats-gender" name="origin">
                                <option value="">Any origin</option>
                                <option value="ios">iOS</option>
                                <option value="web">Website</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-center mb-2 text-muted" style="display: none;">
                        Total of <span data-origin="chart-gender"></span>
                    </div>
                    <div>
                        <canvas id="chart-gender" data-model="users" data-type="gender" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4 p-3">
            <div class="border py-4 px-3">
                <div id="stats-confirmed">
                    <div class="d-flex justify-content-between mb-2">
                        <h4 class="text-center"><strong>Email status</strong></h4>
                          <div class="form-group-sm mx-1">
                            <select class="chart-select form-control form-control-sm" data-chart="pie" data-parent="#stats-confirmed" name="origin">
                                <option value="">Any origin</option>
                                <option value="ios">iOS</option>
                                <option value="web">Website</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-center mb-2 text-muted" style="display: none;">
                        Total of <span data-origin="chart-confirmed"></span>
                    </div>
                    <div>
                        <canvas id="chart-confirmed" data-model="users" data-type="confirmed" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4 p-3">
            <div class="border py-4 px-3">
                <div id="stats-favorites">
                    <div class="d-flex justify-content-between mb-2">
                        <h4 class="text-center"><strong>Favorites</strong></h4>
                    </div>
                    <div class="text-center mb-2 text-muted" style="display: none;">
                        Total of <span data-origin="chart-favorites"></span>
                    </div>
                    <div>
                        <canvas id="chart-favorites" data-model="users" data-type="favorites" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>