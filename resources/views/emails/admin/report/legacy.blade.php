@component('emails.admin.report.template')
# Hi {{$recipient->first_name}}!

Here is the breakdown of our past <u>week</u>.

@foreach($reports as $report)
@include('emails.admin.report.card')
@endforeach

@endcomponent
