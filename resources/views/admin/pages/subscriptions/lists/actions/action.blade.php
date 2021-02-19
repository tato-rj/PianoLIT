<a href="#" data-toggle="modal" data-target="#{{str_slug($list->name)}}-send-modal" class="btn btn-warning btn-sm px-3">Actions</a>

@component('components.modal', ['id' => str_slug($list->name).'-send-modal', 'header' => 'Send email list'])
@slot('body')
{{$before ?? null}}
  <form method="GET" action="{{route('admin.subscriptions.lists.send-to', $list)}}" class="mb-2" disable-on-submit>
    @csrf
    <input type="hidden" name="subject">
    @input([
      'label' => 'Send a preview email to', 
      'value' => null, 
      'name' => 'email', 
      'bag' => 'default', 
      'asterisk' => true])
    <button type="submit" class="btn btn-sm btn-block btn-default">Send preview</button>
  </form>

  <a href="{{route('admin.subscriptions.lists.preview', $list)}}" id="preview-url" target="_blank" class="btn btn-outline-secondary btn-block btn-sm px-3 mr-2">Or just preview in the browser</a>
@endslot

@slot('footer')
  <div class="bg-light px-3 py-2 rounded">
    By clicking the button below you will send this email to the entire list.
    <p class="text-danger"><strong>Are you sure?</strong></p>
    <form method="GET" action="{{route('admin.subscriptions.lists.send', $list)}}" disable-on-submit>
      @csrf
      <input type="hidden" name="subject">
      <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, send the email to the entire list</button>
    </form>
  </div>
@endslot
@endcomponent
