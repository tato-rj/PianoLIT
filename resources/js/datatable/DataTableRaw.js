class DataTableRaw
{
	constructor(params) {
		this.$table = $(params.table);
		this.dontSortFirst = params.hasOwnProperty('dontSortFirst') ? 0 : null;
		this.sortLast = params.hasOwnProperty('sortLast') ? null : $(params.table + ' th').last().index();
		this.options = params.hasOwnProperty('options') ? params.options : {};		
	}

	create() {
		let obj = this;
		let defaultOptions = {
		  aaSorting: [],
		  columnDefs: [ { 'orderable': false, 'targets': [obj.dontSortFirst, obj.sortLast] } ],
		};

		this.$table.DataTable({...defaultOptions, ...obj.options});
	}
}

window.DataTableRaw = DataTableRaw;
