@include('components.account.menu', ['account' => auth('web')->user(), 'designSystemPreview' => $designSystemPreview ?? false])
