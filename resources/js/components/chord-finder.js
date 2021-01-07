$(document).ready(function() {

$(document).on('click', '.chords-results button', function() {
    if (notPlaying) {
        hideDots();
        resetLabel();
        $('.chords-results button').removeClass('btn-chord-selected');
        $(this).addClass('btn-chord-selected');
        let notes = JSON.parse($(this).attr('data-notes'));
        let info = $(this).attr('href');
        let noteIndex = firstIndex = 0;
        let chord = [];

        $('.chord-info').hide();
        $('#info-alert').remove();
        $(info).fadeIn('fast');

        notes.forEach(function(element, index) {
            let note = element;

            note = noteToHumans(note).toLowerCase();

            let $key = findKey(note, noteIndex);

            if ($key == null)
                $key = findKey(note, firstIndex);

            chord.push(note + $key.attr('data-octave'));
            noteIndex = $key.hasClass('keyboard-black-key') ? $key.parent().next().index() : $key.index();
            
            if (firstIndex == 0)
                firstIndex = noteIndex;

            console.log('Playing ' + note + ' at index ' + noteIndex);
            
            setTimeout(function() {
                press($key, 150, false);
                highlight($key);
                updateLabel(note);
            }, 200 * index);
        });

        setTimeout(function() {
            piano.triggerAttackRelease(chord, "1n");
        }, (notes.length + 1) * 200);
    }
});

function updateLabel(note) {
    $label = $('#chord-label');
    $label.append('<div>'+note+'</div>');
}

function resetLabel() {
    $('#chord-label').empty();
}

var input = exclude = include = [];
var root = tool = null;

////////////////////////
// ENHARMONIC OPTIONS //
////////////////////////
$(document).on('click', '#options-container button', function() {
    let $button = $(this);
    resetOptions($button);
    $button.removeClass('btn-outline-secondary').addClass('btn-teal');

    let $selected = $('#options-container button[data-source="'+$button.attr('data-source')+'"].btn-teal');
    let target, source;
    
    target = $selected.attr('data-name');
    source = $selected.attr('data-source');

    notes = [];

    $('#options-container button.btn-teal').each(function() {
        notes.push($(this).attr('data-name'));
    });
    
    console.log('You just selected the ' + target);

    console.log('Set of notes: ' + notes);
    
    input = notes;
        
    if (missingEnharmonics() == 0)
        showRootOptions(input);
});

//////////////////
// ROOT OPTIONS //
//////////////////
$(document).on('click', '#root-container button', function() {
    let $button = $(this);

    $('#root-container button').not(this).removeClass('btn-teal').addClass('btn-outline-secondary');
    
    if ($button.hasClass('btn-outline-secondary')) {
        root = $button.attr('data-name');
        console.log('You just selected ' + root + ' as the root');
    } else {
        root = null;
        console.log('The root has been erased');
    }

    $button.toggleClass('btn-outline-secondary btn-teal');
});

function noteToHumans(note) {
    return ucfirst(note.replace('+', '#').replace('+', '#').replace('-', 'b').replace('-', 'b').replace('2', ''));
}

function noteToMachine(note) {
    let letter = ucfirst(note[0]);

    return letter + note.substring(1).replace('#', '+').replace('#', '+').replace('b', '-').replace('b', '-');    
}

function unique(value, index, self) { 
    return self.indexOf(value) === index;
}

function showRootOptions(notes) {
    let array = notes.filter(unique);
    $('#root-buttons').html('');
    root = null;

    if (array.length > 2) {
        for (var i=0; i<array.length; i++) {
            let html = `<div class="m-2 d-inline-block">
                            <button class="btn btn-outline-secondary font-weight-bold" data-name="`+array[i]+`" type="button">`+noteToHumans(array[i])+`</button>
                        </div>`;

            $('#root-buttons').append(html);
        }
    }
    
    if ($('#root-buttons > div').length > 0) {
        $('#root-container').show();
    } else {
        $('#root-container').hide();
    }
}

function showEnharmonicOptions(notes) {
    $('#options-buttons').html('');

    for (var i=0; i<notes.length; i++) {
        let enharmonics = JSON.parse($('.keyboard-key[data-name="'+noteToHumans(notes[i]).toLowerCase()+'"]').attr('data-names'));

            let html = '<div class="btn-group m-2">';

            enharmonics.forEach(function(enharmonic) {
                html += '<button class="btn btn-outline-secondary font-weight-bold" data-source="'+notes[i]+'" data-name="'+noteToMachine(enharmonic)+'" type="button">'+noteToHumans(enharmonic)+'</button>';
            });
            
            html += '</div>';

            $('#options-buttons').append(html);
        
    }

    if ($('#options-buttons > div').length > 0) {
        $('#options-container').show();
    } else {
        $('#options-container').hide();
    }
}

function resetOptions(element = null) {
    include = [];
    exclude = [];

    if (element) {
        element.siblings('button').removeClass('btn-teal').addClass('btn-outline-secondary');
    } else {
        $('#options-container button').removeClass('btn-teal').addClass('btn-outline-secondary');
    }
}

function removeOptions() {
    resetOptions();
    showEnharmonicOptions([]);
    showRootOptions([]);
}

////////////////////
// KEYBOARD CLICK //
////////////////////
$('.keyboard-input .keyboard-white-key, .keyboard-input .keyboard-white-key').on('click', function(e) {
    let $key = $(e.target);
 
    tool = 'keyboard';
 
    reset('.note');
 
    $key.find(' > .dot').toggle();

    notes = getNotes();

    showEnharmonicOptions(notes);

    showRootOptions([]);
});

$('.dot').on('click', function() {
    $(this).hide();
    getNotes();
});

//////////////////////
// NOTE INPUT CLICK //
//////////////////////
$('.note h1').on('click', function() {
    let $note = $(this).parent();
    $note.toggleClass('note-inactive note-active');
    $note.find('button').toggleAttr('disabled');

    tool = 'button';

    reset('.dot');
    removeOptions();
    playNote($note);
    notes = getNotes();
    showRootOptions(notes);
});

///////////////////////////////////
// NOTE PLUS/MINUS BUTTONS CLICK //
///////////////////////////////////
$('.note button').on('click', function() {
    let symbol = $(this).attr('data-symbol');
    let accidental = '';
    let accidentals = [];
    let $note = $(this).parent().parent();
    let $label = $(this).parent().siblings('h1').find('span');
    let steps = parseInt($label.attr('data-steps'));
    
    if (symbol == '#' && steps < 2) {
        steps += 1;
    } else if (symbol == 'b' && steps > -2) {
        steps -= 1;
    }

    $label.attr('data-steps', steps);

    if (steps == 0)
        $label.text('');

    accidental = steps > 0 ? '#' : 'b';

    for (i=0; i<Math.abs(steps); i++) {
        accidentals.push(accidental);
    }

    $label.text(accidentals.join(''));
    
    let ext = accidentals.join('') == '##' ? 'x' : accidentals.join('');

    $note.attr('data-name', $note.attr('data-name')[0] + ext);
    playNote($note);
    notes = getNotes();
    showRootOptions(notes);
});

function reset(elem) {
    let $notes = $(elem);
    
    if (elem == '.dot') {
        $notes.hide();
    } else {
        $notes.removeClass('note-active').addClass('note-inactive');
        $notes.find('button').prop('disabled', true);
    }
}

function playNote($note) {
    if ($note.hasClass('note-active'))
        play($note.attr('data-name'), $note.attr('data-octave'), 500);
}

function getNotes() {
    let notes = [];

    $('.note-active').each(function() {
        let note = $(this).find('h1').text();
        note = note.replace(/#|_/g, '+');
        note = note.replace(/b|_/g, '-');
        notes.push(note);
    });

    $('.keyboard-key').each(function() {
        $key = $(this);

        if ($key.find('>.dot:visible').length > 0) {
            let note = $key.attr('data-name').toUpperCase();
            
            notes.push(note.replace(/#|_/g, '+'));
        }
    });

    input = notes;

    $('#reset-options').attr('data-original', JSON.stringify(notes));

    return notes;
}

////////////////////////
// SUBMIT NOTES CLICK //
////////////////////////
$('button#submit-notes').on('click', function() {
    if (missingEnharmonics() > 0) {
            alert('Please select each enharmonic note, you\'re missing ' + missingEnharmonics());
            $('#options-container').removeClass('bounce').addClass('bounce');
    } else if (input.length < 3) {
        alert('Please select at least 3 notes');
    } else {
        $(this).prop('disabled', true);
        $(this).text('Hang on a sec...');

        animate();
        setTimeout(function() {
            submit();
        }, 1500);
    }
});

function missingEnharmonics() {
    return $('#options-buttons .btn-group').length - $('#options-buttons .btn-teal').length;
}

function nextLetter(letter) {
    let next = letter.substring(0, letter.length - 1) + String.fromCharCode(letter.charCodeAt(letter.length - 1) + 1);

    if (next == 'H')
        return 'A';

    return next;
}

function animate() {
    $('.input-overlay').show();
}

function submit() {
    let $container = $('#notes-container');
    console.log('Sending: ' + input);
    console.log('Tool used: ' + tool);
    console.log(root ? 'The selected root is ' + root : 'No root was selected');

    axios.get($container.attr('data-url'), {params: {notes: input, root: root, tool: tool}})
        .then(function(response) {
        $container.html(response.data);
        $('html,body').scrollTop(0);
    }).catch(function(error) {
        showError(error.response.data.message);
    });
}

function showError(response) {
    $('#modal-error .modal-body div#error-report').text(response);
    $('#modal-error').modal('show');
}

$('#modal-error').on('hide.bs.modal', function() {
    reload();
});

function reload(full = false) {
    $('button#submit-notes').text($('button#submit-notes').attr('data-text')).prop('disabled', false);
    $('.input-overlay').hide();
    
    if (full) {
        input = [];
        reset('.dot');
        reset('.note');
        $('#options-buttons').html('').parent().removeClass('border');
        $('#options-container').hide();
        $('#root-buttons').html('');
        $('#root-container').hide();
    }
}

function updateUrl(notes) {
    let query = '?';
    notes.forEach(function(note) {
        query += 'notes[]=' + note.replace('+', 's') + '&';
    });
    if (history.pushState) {
        let newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + query;
        window.history.pushState({path:newurl},'',newurl);
    }
}

});