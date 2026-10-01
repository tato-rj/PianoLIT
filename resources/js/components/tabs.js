$('#main-nav a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
  let $tab = $(e.target);
  $('#nav-border').match($tab);
  $('#nav-border').show();
});