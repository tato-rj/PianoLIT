class Search
{
    constructor(params) {
        this.request = null;
    }

    listenTo(input) {
        this.$input = $(input);
        this.url = this.$input.attr('data-url');

        return this;
    }

    feedbackIn(element) {
        this.$loading = $(element).find('>div[loading]');
        this.$error = $(element).find('>div[error]');

        return this;
    }

    resultsIn(element) {
        this.$results = $(element);

        return this;
    }

    ready() {
        this._init();
    }

    _init() {
        let obj = this;

        obj.$input.on('keyup', function() {
            let originalVal = $(this).val();
            let params = {search: obj.$input.val()};

            clearTimeout(obj.request);

            obj.request = setTimeout(function () {
                obj._prepare();
                
                if (obj._inputGreaterThan(2)) {
                    obj.$results.empty();
                    obj._call(params, originalVal);
                } else {
                    obj._reset();
                }
            }, 650);
        });
    }

    _call(params, originalVal) {
        let obj = this;
        axios.get(obj.url, {params: params})
        .then(function(response) {
            if (obj.$input.val() == originalVal) {
                console.log(response.data);
                obj.$results.html(response.data);
            }
        })
        .catch(function(error) {
            console.log(error);
            obj.$results.empty();
            obj.$error.show();
        })
        .then(function() {
            obj.$loading.hide();
        });
    }

    _prepare() {
        this.$results.empty();
        this.$loading.show();
        this.$error.hide();
    }

    _reset() {
        this.$results.empty();
        this.$loading.hide();
        this.$error.hide();
    }

    _inputGreaterThan(num) {
        return this.$input.val().length > num;
    }
}

window.Search = Search;