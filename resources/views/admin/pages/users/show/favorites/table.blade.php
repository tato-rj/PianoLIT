<div>
  <table class="table table-hover w-100 border" id="favorites-table">
    <thead>
      <tr>
        <th onclick="w3.sortHTML('#favorites-table', '.sortable', 'td:nth-child(1)')" style="cursor:pointer" class="border-0" scope="col">
          Piece <i class="fas fa-sort"></i></th>
        <th onclick="w3.sortHTML('#favorites-table', '.sortable', 'td:nth-child(2)')" style="cursor:pointer" class="border-0" scope="col">
          Composer <i class="fas fa-sort"></i></th>
        <th onclick="w3.sortHTML('#favorites-table', '.sortable', 'td:nth-child(3)')" style="cursor:pointer" class="border-0" scope="col">
          Level <i class="fas fa-sort"></i></th>
      </tr>
    </thead>
    <tbody>
      @include('admin.pages.users.show.favorites.rows', ['pieces' => $user->favorites, 'limit' => 5, 'more' => true])
    </tbody>
  </table>
</div>