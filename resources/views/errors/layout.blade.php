{{-- Compatibility for existing templates extending the earlier error layouts. --}}
@include('errors.page', ['status' => (int) $__env->yieldContent('code', isset($exception) ? $exception->getStatusCode() : 500)])
