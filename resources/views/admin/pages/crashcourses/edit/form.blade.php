        <form method="POST" action="{{route('admin.crashcourses.update', $crashcourse)}}" autocomplete="off" enctype="multipart/form-data">
          @csrf
          @method('PATCH')
            @image(['name' => null, 'image' => $crashcourse->cover_image(), 'empty' => true])
            <div class="rounded bg-light px-3 py-2 mb-3">
              <p class="text-brand border-bottom pb-1 mb-1"><strong>TOPICS</strong></p>
              <div class="d-flex flex-wrap">
                  @foreach($topics as $topic)
                  <div class="custom-control custom-checkbox mx-2 mb-2">
                    <input type="checkbox" class="custom-control-input" name="topics[]" value="{{$topic->id}}" id="topic-{{$topic->name}}" {{($crashcourse->topics->contains($topic->id)) ? 'checked' : ''}}>
                    <label class="custom-control-label" for="topic-{{$topic->name}}">{{$topic->name}}</label>
                  </div>
                  @endforeach
              </div>
            </div>

            @input(['bag' => 'default', 'value' => $crashcourse->title, 'name' => 'title', 'placeholder' => 'Crash Course title', 'limit' => 120])
            @textarea(['bag' => 'default', 'value' => $crashcourse->description, 'name' => 'description', 'placeholder' => 'Crash Course description', 'limit' => 238])

            <div>
              <button type="submit" id="submit-button" class="btn btn-default">Update course</button>
              @include('admin.components.creator', ['model' => $crashcourse, 'type' => 'course'])
            </div>
        </form>