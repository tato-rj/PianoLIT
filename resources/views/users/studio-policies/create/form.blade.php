@foreach(['general', 'performances', 'lessons', 'materials', 'scheduling', 'makeups', 'agreements'] as $step)
@include('users.studio-policies.create.steps.' . $step, ['loop' => $loop])
@endforeach