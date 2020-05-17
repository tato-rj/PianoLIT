@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
</style>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'My Membership', 'subtitle' => 'Manage your account details here'])
<section class="text-center">
  <div class="d-inline-block border rounded py-4 px-5 mb-4">
    <p class="mb-2 lead">You have a membership with</p>
    <h4 class="m-0">@fa(['icon' => 'apple', 'fa_type' => 'b'])In-app Purchase</h4>
  </div>
  <p>In order to make any changes to your membership plan or update your payment method, please refer to Apple's guidelines found <a target="_blank" class="link-blue" href="https://support.apple.com/billing">here</a>.</p>
  <p>If you have any questions, email us at <a class="link-blue" href="{{config('app.emails.general')}}">{{config('app.emails.general')}}</a>.</p>
</section>

@endsection
@push('scripts')
<script type="text/javascript">
$('#faq-accordion').on('show.bs.collapse', function (event) {
  $(event.target).siblings('div').find('i').removeClass('fa-plus').addClass(' fa-minus');
});

$('#faq-accordion').on('hide.bs.collapse', function (event) {
  $(event.target).siblings('div').find('i').removeClass('fa-minus').addClass(' fa-plus');
});
</script>
<script type="text/javascript">
</script>
@endpush