<?php

namespace App\Http\Controllers;

use App\User;
use App\Auth\Google\Accounts;
use App\Services\Profile\{CloseAccount, EmailChange, EmailPreferences};
use App\Support\ProfileUrl;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\{Rule, ValidationException};

class ProfileController extends Controller
{
    private const MEMBERSHIP_REVIEW = 'Please close your membership before deleting your account. If it has already ended, contact us so we can review it.';

    public function show(Request $request, Accounts $accounts, EmailPreferences $preferences)
    {
        $user = $request->user('web')->loadMissing('location');
        $subscribedListIds = $preferences->subscribers($user)->with('lists')->get()->flatMap(function ($subscriber) {
            return $subscriber->getRelation('lists')->pluck('id');
        })->unique();
        $links = [];
        foreach (['google', 'apple'] as $provider) {
            $configuration = $provider === 'google' ? \App\Auth\Google\Configuration::class : \App\Auth\Apple\Configuration::class;
            $link = $accounts->linkFor($user, $provider);
            $links[$provider] = ['account' => $link, 'available' => (bool) app($configuration)->available($request),
                'canDisconnect' => $link ? $accounts->otherMethod($user, $provider) : false];
        }
        return view('users.profile.index', ['user' => $user, 'links' => $links, 'lists' => $preferences->lists(),
            'subscribedListIds' => $subscribedListIds,
            'hasPassword' => $accounts->hasPassword($user), 'recentProof' => $accounts->recent($request, $user),
            'removalBlocked' => app(\App\Services\Admin\UserRemovalPolicy::class)->reason($user->id) ? self::MEMBERSHIP_REVIEW : null]);
    }

    public function details(Request $request)
    {
        $data = $request->validateWithBag('profileDetails', [
            'first_name' => 'required|string|max:200', 'last_name' => 'nullable|string|max:200',
        ]);
        $data['last_name'] = $data['last_name'] ?? '';
        DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user('web')->id)->lockForUpdate()->firstOrFail()->update($data);
        }, 3);
        return redirect(ProfileUrl::get('profile'))->with('status', 'Your profile has been updated.');
    }

    public function email(Request $request, EmailChange $changes)
    {
        $request->validateWithBag('emailChange', ['email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($request->user('web')->id)]]);
        if ($response = $this->proof($request, 'emailChange')) return $response;
        $email = mb_strtolower(trim($request->email));
        $user = DB::transaction(function () use ($request) {
            $user = User::whereKey($request->user('web')->id)->lockForUpdate()->firstOrFail();
            $this->requireProof($request, $user, 'emailChange');
            return $user;
        }, 3);
        if ($email === mb_strtolower($user->email)) return redirect(ProfileUrl::get('security'))->with('status', 'This is already your account email address.');
        if (User::whereRaw('LOWER(email) = ?', [$email])->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'This email address is already in use.'])->errorBag('emailChange');
        }
        $changes->send($user, $email);
        return redirect(ProfileUrl::get('security'))->with('status', 'Check your new email address for a confirmation link. Your current email stays in place until you confirm.');
    }

    public function verifyEmail(Request $request, EmailChange $changes)
    {
        $request->validate(['token' => 'required|string|max:4096']);
        $email = $changes->pending($request->user('web'), $request->token);
        if ($request->isMethod('GET')) return view('users.profile.confirm-email', compact('email'));
        $user = $changes->apply($request->user('web')->id, $request->token);
        auth('web')->setUser($user);
        $request->session()->regenerate();
        app(Accounts::class)->verified($request, $user);
        return redirect(ProfileUrl::get('security'))->with('status', 'Your email address has been updated. Use your new address next time you sign in.');
    }

    public function password(Request $request)
    {
        $request->validateWithBag('passwordChange', ['password' => 'required|string|min:8|max:1024|confirmed']);
        if ($response = $this->proof($request, 'passwordChange')) return $response;
        $user = DB::transaction(function () use ($request) {
            $user = User::whereKey($request->user('web')->id)->lockForUpdate()->firstOrFail();
            $this->requireProof($request, $user, 'passwordChange');
            $user->forceFill(['password' => Hash::make($request->password), 'remember_token' => Str::random(60)])->save();
            DB::table('user_auth_providers')->where('user_id', $user->id)->update(['unusable_password_hash' => null, 'updated_at' => now()]);
            DB::table('password_resets')->where('email', $user->email)->delete();
            return $user;
        }, 3);
        event(new PasswordReset($user));
        auth('web')->setUser($user);
        $request->session()->regenerate();
        app(Accounts::class)->verified($request, $user);
        return redirect(ProfileUrl::get('security'))->with('status', 'Your password has been updated.');
    }

    public function subscription(Request $request, \App\EmailList $list, EmailPreferences $preferences)
    {
        $request->validate(['enabled' => 'required|boolean']);
        $enabled = $preferences->save($request->user('web')->id, $list, $request->boolean('enabled'));
        return $request->expectsJson() ? response()->json(['subscribed' => $enabled, 'message' => 'Email preferences saved.'])
            : redirect(ProfileUrl::get('email-preferences'))->with('status', 'Your email preferences have been saved.');
    }

    public function unsubscribeAll(Request $request, EmailPreferences $preferences)
    {
        $preferences->leaveAll($request->user('web')->id);
        return redirect(ProfileUrl::get('email-preferences'))->with('status', 'You have unsubscribed from all email lists.');
    }

    public function destroy(Request $request, CloseAccount $closure)
    {
        $request->validateWithBag('deleteAccount', ['confirmation_email' => 'required|string|email|max:255']);
        if ($response = $this->proof($request, 'deleteAccount')) return $response;
        try {
            DB::transaction(function () use ($request, $closure) {
                $user = User::whereKey($request->user('web')->id)->lockForUpdate()->firstOrFail();
                $this->requireProof($request, $user, 'deleteAccount');
                $closure->close($user->id, $request->confirmation_email);
            }, 3);
        } catch (ValidationException $exception) {
            if (array_key_exists('account_removal', $exception->errors())) {
                throw ValidationException::withMessages(['account_removal' => self::MEMBERSHIP_REVIEW])->errorBag('deleteAccount');
            }
            throw $exception;
        }
        // SessionGuard::logout would save a rotated remember token and reinsert
        // a deleted Eloquent model. Clear the guard without writing the old user.
        auth('web')->logoutCurrentDevice();
        app(\App\Services\CampaignAttribution::class)->clearCookies($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(ProfileUrl::base().'/')->with('status', 'Your account has been permanently deleted. Any future signup will create a new account.');
    }

    private function proof(Request $request, $bag)
    {
        $user = User::findOrFail($request->user('web')->id);
        $accounts = app(Accounts::class);
        if (!$accounts->hasPassword($user) && !$accounts->recent($request, $user)) {
            return app(\App\Http\Controllers\Auth\GoogleController::class)->confirm($request, 'profile');
        }
        $this->requireProof($request, $user, $bag);
        return null;
    }

    private function requireProof(Request $request, User $user, $bag)
    {
        $accounts = app(Accounts::class);
        if ($accounts->recent($request, $user)) return;
        if (!$accounts->hasPassword($user) || !is_string($request->current_password) || strlen($request->current_password) > 1024
            || !Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Enter your current PianoLIT password to confirm this change.'])->errorBag($bag);
        }
    }
}
