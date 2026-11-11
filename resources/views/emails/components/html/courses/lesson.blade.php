<style>
.course-content table, .course-content td, .course-rich a { font-family:inherit; }
.course-rich p, .course-rich li, .course-rich h1, .course-rich h2, .course-rich h3, .course-rich div { color:inherit; font-family:inherit; text-align:inherit; }
.course-rich p { font-size:16px; line-height:1.65; margin:0 0 16px; }
.course-rich img { max-width:100%; height:auto; }
.course-rich table { max-width:100%; }
@media only screen and (max-width:620px) { .course-column { display:block !important; width:100% !important; padding:0 0 16px !important; } }
</style>
<tr><td class="welcome-padding" align="center" style="padding-top:32px;padding-bottom:32px;">
<a href="{{ \App\Support\EmailLayout::websiteUrl() }}">@component('mail::editorial.logo', ['websiteUrl' => \App\Support\EmailLayout::websiteUrl()])@endcomponent</a>
</td></tr>
<tr><td class="welcome-padding">
@include('emails.courses.content')
</td></tr>
