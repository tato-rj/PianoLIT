@component('admin.components.editor.section', ['id' => 'quiz-details', 'sectionId' => 'quiz-details', 'title' => 'Quiz details'])
    @input(['bag' => 'default', 'id' => 'quiz-title', 'name' => 'title', 'label' => 'Title', 'value' => optional($quiz)->title, 'limit' => 120])
    @textarea(['bag' => 'default', 'id' => 'quiz-description', 'name' => 'description', 'label' => 'Description', 'value' => optional($quiz)->description, 'limit' => 238, 'rows' => 3])
    @select(['bag' => 'default', 'id' => 'quiz-level', 'name' => 'level_id', 'label' => 'Level', 'choices' => ['' => 'Choose a level'] + $levels->pluck('name', 'id')->all(), 'value' => optional($quiz)->level_id])
@endcomponent
