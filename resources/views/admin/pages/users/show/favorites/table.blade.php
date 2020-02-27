<div>
  <table class="table table-hover w-100 border" id="favorites-table">
    <thead>
      <tr>
        <th class="border-0" scope="col">Piece</th>
        <th class="border-0" scope="col">Composer</th>
        <th class="border-0" scope="col">Level</th>
      </tr>
    </thead>
    <tbody>
      @include('admin.pages.users.show.favorites.rows', ['pieces' => $user->favorites, 'limit' => 5, 'more' => true])
    </tbody>
  </table>
</div>