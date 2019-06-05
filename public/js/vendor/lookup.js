function Lukup(obj)
{
  this.debounceTimeout = null;
  this.request = null;
  this.url = obj.url;
  this.autofill = obj.autofill;
  this.exclude = obj.exclude || [];
  this.input = $('input[name="'+obj.field+'"]');
  this.wrapper = this.input.parent();
  this.field = this.input.attr('name');

  this.prepareMenu = function() {
    var clone = this.resultsContainerModel.clone().removeClass('model').addClass('autocomplete-temp').appendTo(this.wrapper);
    clone.show();

    return clone;
  }

  this.createHtmlElements = function() {
    var elements = $('<div class="autocomplete model position-absolute w-100 px-1" style="top: 100%; left: 0; display: none; z-index: 5"><div class="bg-white border"><div class="text-muted loading px-2 py-1 bg-light border-bottom"><i><small><i class="fas fa-search mr-2"></i><strong>Finding similar...</strong></small></i></div><div class="model hover-bg-light text-muted px-2 py-1 cursor-pointer d-none"><i><small><span class="text-temp"></span></small></i></div></div></div>');
    this.wrapper.css('position', 'relative').append(elements);
    this.resultsContainerModel = elements;
  }

  this.fillElements = function(element) {
    let lookup = this;

    this.autofill.forEach(field => {
      value = $(element).attr('data-'+field);
      if (! lookup.exclude.includes(value)) {
        if (value) {
          $('input[name="'+field+'"], textarea[name="'+field+'"]').val(value).addClass('border-warning');
          $('select[name="'+field+'"] option[value="'+value+'"]').prop('selected', true).parent().addClass('border-warning');
        } else {
          $('input[name="'+field+'"], textarea[name="'+field+'"]').val(value).removeClass('border-warning');
          $('select[name="'+field+'"] option[value="'+value+'"]').prop('selected', true).parent().removeClass('border-warning');
        }
      }
    });
  }

  this.reset = function() {
    $('.autocomplete-temp').remove();
  }

  this.autocomplete = function() {
    this.reset();
    
    if (this.input.val() == '')
      return;

    if (this.request)
      this.request.abort();
    
    var menu = this.prepareMenu();
    var autofill = this.autofill;

    this.request = $.post(this.url, {
        field: this.field,
        input: this.input.val()
      }, function(data, status){
        console.log('Searching with Lukup!');
        console.log('Data: ' + data);
        // GET RESULTS
        data.forEach(result => {
          var container = menu.find('.model').clone().removeClass('model').appendTo(menu.find('div.border'));
          container.addClass('result-temp');
          container.find('span').text(result.output);
          autofill.forEach(field => {
            container.attr('data-'+field, result[field]);
          });
        });
        // SHOW RESULTS
        menu.find('.loading strong').text('Found '+data.length+' result(s)!');
        menu.find('.result-temp').removeClass('d-none');      
    });
  };

  this.enable = function() {
    var lookup = this;

    lookup.createHtmlElements();

    $(lookup.wrapper).on('click', '.result-temp', function() {
      lookup.fillElements(this);
    });

    $(lookup.wrapper).on('keyup', this.input, function(event){
      clearTimeout(lookup.debounceTimeout);
      lookup.debounceTimeout = setTimeout(lookup.autocomplete(), 200);
    });

    // HIDE AUTOCOMPLETE IF CLICK ANYWHERE ON THE SCREEN
    $(document).on('click', function(e) {     
        lookup.reset();
    }); 
  }
}
