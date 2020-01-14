class DataTable
{
	constructor(table) {
		this.$table = $(table);
		this.sortColumn = 0;
		this.sortOrder = 'desc';
	}

	columns(array) {
		let obj = this;
		obj.columns = array;

		array.some( function(element, index) {
			if (element.hasOwnProperty('sort')) {
				obj.sortColumn = index;
				return true;
			}
		});

		return this;
	}

	create() {
		let obj = this;

	    let table = obj.$table.DataTable({
	        processing: true,
	        serverSide: true,
	        aaSorting: obj._sortColumn(),
	        language: {processing: `<div class="overlay-pulse"></div>`},
	        ajax: window.location.href,
	        columns: obj.columns,
	        initComplete: function(settings, json) {
	          obj.$table.find('thead').removeClass('invisible');
	          $('.dataTables_wrapper').removeClass('table-loading');
	        }
	    });

	    $(table.table().container()).addClass('table-loading');
	}

	order(sortOrder) {
		this.sortOrder = sortOrder;

		return this;
	}

	dontSort() {
		this.sortColumn = null;
		this.sortOrder = null;

		return this;	
	}

	_sortColumn() {
		if (this.sortOrder)
			return [this.sortColumn, this.sortOrder];

		return [];
	}
}

window.DataTable = DataTable;
