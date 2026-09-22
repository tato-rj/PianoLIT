class Search
{
    constructor(params) {
        this.request = null;
        this.requestId = 0;
        this.cancelRequest = null;
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

        obj.$input.on('input', function() {
            let requestId = ++obj.requestId;
            let originalVal = $(this).val();
            let params = {search: obj.$input.val()};

            clearTimeout(obj.request);
            if (obj.cancelRequest) {
                obj.cancelRequest();
                obj.cancelRequest = null;
            }

            obj.request = setTimeout(function () {
                obj._prepare();
                
                if (obj._inputGreaterThan(2)) {
                    obj.$results.empty();
                    obj._call(params, originalVal, requestId);
                } else {
                    obj._reset();
                }
            }, 650);
        });
    }

    _call(params, originalVal, requestId) {
        let obj = this;
        let cancellation = axios.CancelToken.source();
        obj.cancelRequest = cancellation.cancel;
        axios.get(obj.url, {params: params, cancelToken: cancellation.token})
        .then(function(response) {
            if (obj.requestId === requestId && obj.$input.val() === originalVal) {
                obj.$results.html(response.data);
            }
        })
        .catch(function(error) {
            if (obj.requestId !== requestId || axios.isCancel(error)) return;
            obj.$results.empty();
            obj.$error.show();
        })
        .then(function() {
            if (obj.requestId === requestId) {
                obj.$loading.hide();
                obj.cancelRequest = null;
            }
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
