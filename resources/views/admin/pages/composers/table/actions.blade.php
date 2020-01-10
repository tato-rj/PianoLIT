<div class="text-right"> 
  @include('components.datatable.actions', ['actions' => [
      'other' => [['route' => route('email-preview.birthday.web', ['composer_id' => $item->id]), 'title' => 'See a preview of the birthday email', 'icon' => 'birthday-cake']],
      'edit' => route('admin.composers.edit', $item->id),
      'delete' => route('admin.composers.destroy', $item->id)
  ]])
</div>