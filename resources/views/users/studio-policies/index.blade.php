@extends('layouts.app')

@push('header')
<style type="text/css">

</style>
@endpush

@section('content')

<div class="container mb-4">
	<div class="row mb-6">
		<div class="col-lg-8 col-12 mx-auto">
			<div class="mb-4">
				<h4>My policies</h4>
				<p>Here is the list of all the policies you have created.</p>
			</div>
			<div class="row"> 
				@forelse(auth()->user()->studioPolicies as $policy)
					@include('users.studio-policies.card')			
				@empty
				<div class="col-12">
					<div class="border rounded px-3 py-5 my-4 text-center">
						<h2 class="text-grey mb-1"><i class="far fa-folder-open"></i></h2>
						<h5 class="text-grey m-0">You have not created a policy yet</h5>
					</div>
				</div>
				@endforelse
			</div>
		</div>
	</div>

	<div class="row mb-6">
		<div class="col-lg-12 text-center">
			<h5 class="mb-3">Do you want to create a new one?</h5>
			<a href="{{route('users.studio-policies.create')}}" class="btn btn-primary shadow"><i class="fas fa-magic mr-2"></i>Create a new policy now</a>
		</div>
	</div>
	
	<div class="row mb-6">
		<div class="col-lg-8 col-12 mx-auto">
			<p><strong>Why do I need a studio policy?</strong></p>
			<p>An effective studio policy is one of the most important tools a piano teacher has for creating a private studio. Designing your piano studio policy gives you the opportunity to reflect on what you hope to accomplish in your studio and how you will handle the tricky situations that are certain to arise.</p>
			{{-- <p><a href="" class="link-blue">Click here</a> if you want to learn more.</p> --}}
		</div>
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@include('admin.components.modals/delete', ['model' => 'policy'])
@endsection

@push('scripts')
<script type="text/javascript">
$('.delete').on('click', function (e) {
  $('#delete-modal').find('form').attr('action', $(this).attr('data-url'));
})
</script>
@endpush