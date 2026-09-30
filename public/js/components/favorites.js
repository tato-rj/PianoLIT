    $('a.toggle-favorite span').click(function() {
    $(this).siblings('button').click();
});
    $(document).on('click', 'button[data-manage=save-to]', function() {
        let $btn = $(this);
        $btn.disable();

        axios.get($btn.data('url'))
            .then(function(response) {
                $('#bottom-popup-content').html(response.data)
                $('#bottom-popup-content > div').width($('main').width());
                $('#bottom-popup').show();
            })
            .catch(function(error) {
                $('#bottom-popup').fadeOut('fast');
            })
            .then(function() {
                $btn.enable();
            });
    });

    $(document).on('click', 'button[data-submit=favorite]', function() {
        let $btn = $(this);
        let $btns = $btn.parent().find('button');
        let $container = $btn.closest('#favorite-folders-container');
        let scrollTop = $container.find('.save-to-panel__list').scrollTop();
        $btns.disable();

        axios.post($btn.data('url'))
            .then(function(response) {
                if ($container.length) {
                    $container.html(response.data.html.list);
                    $container.find('.save-to-panel__list').scrollTop(scrollTop);
                }
                updateFlag($btn.data('target'));
            })
            .catch(function(error) {
                alert('Something went wrong...');
                console.log(error);
            })
            .then(function() {
                $btns.enable();
            });
    });

    $(document).on('click', 'button[data-submit=folder]', function() {
        let $btn = $(this);
        let $name = $($btn.data('name'));
        let $container = $('#favorite-folders-container');

        $btn.disable().addLoader();
        $btn.siblings('button').hide();

        axios.post($btn.data('url'), {name: $($name).val()})
            .then(function(response) {
                $container.html(response.data.html.list);
                $container.find('.invalid-feedback').text('').hide();
            })
            .catch(function(error) {
                $btn.enable().removeLoader();
                $btn.siblings('button').show();
                let feedback = objFirst(error.response.data.errors)[0];
                $container.find('.invalid-feedback').text(feedback).show();
            })
            .then(function() {
                //
            });
    });

    function updateFlag(flag) {
        $(flag).find('i').toggleClass('icon-filled');
    }

    $(document).on('click', 'button.new-folder', function() {
        $(this).hide();
        $($(this).data('target')).show();
    });

    $(document).on('click', 'button.cancel-new-folder', function() {
        $($(this).data('container')).hide();
        $($(this).data('target')).show();
    });
