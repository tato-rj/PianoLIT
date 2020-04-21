var hash = window.location.hash;
hash && $('.list-group .list-group-item[href="' + hash + '"]').tab('show');