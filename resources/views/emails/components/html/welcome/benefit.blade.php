<tr><td class="welcome-benefit" align="center">
<img class="welcome-benefit-icon" src="{{ $image }}" width="40" height="40" alt="">
<h3>{{ $title }}</h3>
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td></tr>
