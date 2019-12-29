<form method="GET" action="{{route('admin.api.search')}}" class="mb-4">
  <div class="input-group">
    <div class="input-group-prepend">
      <i class="fas fa-lightbulb bg-white text-brand border-0 input-group-text" style="line-height: 1.5"></i>
    </div>
    <input type="hidden" name="lazy-load">
    <input 
      type="text" 
      name="search" 
      placeholder="Search here..."
      style="border-bottom: 1px solid lightgrey !important;"
      value="{{str_rm(request('search'), '"')}}" 
      required 
      class="form-control border-0 rounded-0">
    <button class="btn btn-sm btn-default ml-2">Search</button>
  </div>
</form>