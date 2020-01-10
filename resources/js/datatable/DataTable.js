class DataTable
{
	constructor(table) {
		this.$table = $(table);
		this.sortBy = 0;
		this.orderBy = 'desc';
	}

	columns(array) {
		let obj = this;
		obj.columns = array;

		array.some( function(element, index) {
			if (element.hasOwnProperty('sort')) {
				obj.sort = index;
				return true;
			}
		});

		return this;
	}

	create() {
		let obj = this;

	    obj.$table.DataTable({
	        processing: true,
	        serverSide: true,
	        aaSorting: obj._sortBy(),
	        language: {processing: `<div class="overlay-pulse"></div>`},
	        ajax: window.location.href,
	        columns: obj.columns,
	        initComplete: function(settings, json) {
	          obj.$table.find('thead').removeClass('invisible');
	          $('.datatable-loading').remove();
	        }
	    });
	}

	order(orderBy) {
		this.orderBy = orderBy;

		return this;
	}

	dontSort() {
		this.sortBy = null;
		this.orderBy = null;

		return this;	
	}

	_sortBy() {
		if (this.orderBy)
			return [this.sortBy, this.orderBy];

		return [];
	}
}

window.DataTable = DataTable;
