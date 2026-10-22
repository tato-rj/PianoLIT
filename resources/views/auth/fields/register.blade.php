@input(['name' => 'first_name', 'placeholder' => 'First name', 'autocomplete' => 'given-name'])
@input(['name' => 'last_name', 'placeholder' => 'Last name', 'autocomplete' => 'family-name'])
@input(['type' => 'email', 'name' => 'email', 'placeholder' => 'Email', 'autocomplete' => 'email'])
@input(['type' => 'password', 'name' => 'password', 'placeholder' => 'Password', 'autocomplete' => 'new-password'])
@input(['type' => 'password', 'name' => 'password_confirmation', 'placeholder' => 'Confirm your password', 'autocomplete' => 'new-password'])

<input type="hidden" name="origin" value="{{ onWebApp() ? 'webapp' : 'web' }}">
