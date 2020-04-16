@foreach($birthdays as $composer)
@alert([
  'color' => 'warning',
  'fullX' => true,
  'message' => '<i class="fas fa-birthday-cake mr-2"></i>' . $composer->name  . ' was born on this day ' . now()->diffInYears($composer->date_of_birth) . ' years ago on ' . $composer->date_of_birth->toFormattedDateString(),
  'dismissible' => true])
@endforeach
@foreach($deathdays as $composer)
@alert([
  'color' => 'secondary',
  'fullX' => true,
  'message' => '<i class="fas fa-skull mr-2"></i>' . $composer->name  . ' died on this day ' . now()->diffInYears($composer->date_of_death) . ' years ago on ' . $composer->date_of_death->toFormattedDateString(),
  'dismissible' => true])
@endforeach