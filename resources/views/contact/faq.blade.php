<section id="account" class="support-section" aria-labelledby="account-heading">
    <h2 id="account-heading">Account & sign-in</h2>
    @component('components.layout.accordion', ['id' => 'support-account-faq'])
        @component('components.layout.accordion-item', ['id' => 'support-sign-in', 'title' => 'How do I create an account or sign in?'])
            <p><a href="{{ route('register') }}">Create a PianoLIT account</a> or <a href="{{ route('login') }}">sign in</a> on the website. In the iOS app, use your PianoLIT email and password. If you normally use Google or Apple on the website, you can set a password in <a href="{{ route('users.profile') }}#security">My profile</a> for email sign-in.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-password', 'title' => 'I forgot my password or can’t sign in'])
            <p><a href="{{ route('password.request') }}">Request a password reset</a> using your PianoLIT account email, then check your inbox and spam folder. If you signed up with Google or Apple, try that sign-in option on the website. With Apple’s Hide My Email, your account may use a private relay address. If you are still stuck, contact us rather than creating another account.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-account-details', 'title' => 'How do I change my email, password, or linked accounts?'])
            <p>Open <a href="{{ route('users.profile') }}#security">My profile → Security & linked accounts</a>. Email changes require proof of your current account and confirmation at your new address. Changing your PianoLIT email does not change the Apple Account used for App Store billing.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-emails', 'title' => 'How do I choose which emails I receive?'])
            <p>Use <a href="{{ route('users.profile') }}#email-preferences">My profile → Email preferences</a> to choose individual lists or unsubscribe from all available lists. You can also use the unsubscribe link in an email. Email preferences are separate from your paid membership.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-delete-account', 'title' => 'How do I delete my account?'])
            <p>Open <a href="{{ route('users.profile') }}#delete-account">My profile → Delete account</a> and review the details before confirming. An active membership must end first; cancel it with the provider that bills you. Account deletion is permanent. A later signup creates a new account without restoring your saved content or purchase history.</p>
            <p>Some billing, purchase, and activity records are retained separately. See our <a href="{{ route('privacy') }}">Privacy policy</a> or contact us with a privacy question.</p>
        @endcomponent
    @endcomponent
</section>

<section id="using-pianolit" class="support-section" aria-labelledby="using-heading">
    <h2 id="using-heading">Using PianoLIT</h2>
    @component('components.layout.accordion', ['id' => 'support-using-faq'])
        @component('components.layout.accordion-item', ['id' => 'support-free-premium', 'title' => 'What can I use for free, and what needs Premium?'])
            <p>You can browse repertoire and open piece pages in the web app without a membership. An account unlocks personal features; full media, scores, and other Premium features depend on your access. The page will explain when sign-in or Premium is needed. See <a href="{{ route('webapp.membership.pricing') }}">current membership plans</a> for details.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-free-pick', 'title' => 'How does the Free Pick work?'])
            <p>The current Free Pick gives signed-in web users full access to its performance, Synthesia video, audio, and score, with saved annotations. Guests get previews. Downloading or printing still requires membership. When the Free Pick changes, regular access rules apply again; your saved annotations remain for when you regain access.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-favorites', 'title' => 'How do I save or organize pieces?'])
            <p>In the web app, sign in and select a piece’s heart to open Save to folder. Choose a folder there to save or remove the piece. A filled heart means it is saved in at least one of your folders. Folder and piece limits depend on your account’s access.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-scores', 'title' => 'How do scores, annotations, and downloads work?'])
            <p>Open a piece’s Score tab. When you have access, you can add markings to supported scores while signed in. Markings belong to your account and that specific score. Score downloads contain the original PDF without your annotations; download and print availability depends on your access.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-playback', 'title' => 'A video, audio track, score, or tool isn’t working'])
            <p>If you see a sign-in or Premium message, follow that prompt first. Otherwise:</p>
            <ol class="ps-3">
                <li>Check your connection and reload the page or reopen the app.</li>
                <li>Check device volume and the player’s mute setting.</li>
                <li>Try an updated browser or the latest available iOS app version.</li>
            </ol>
            <p>If the issue continues, <a href="#contact-support">send us the page or piece name</a>, your device details, and any error message. For metronome voice recordings, allow microphone access when prompted.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-content-feedback', 'title' => 'Can I report a mistake or suggest something?'])
            <p>Yes. Use the feedback button in the web app, or <a href="#contact-support">email us</a>. Include a link to the piece, article, or tool and describe the correction or idea.</p>
        @endcomponent
    @endcomponent
</section>

<section id="purchases" class="support-section" aria-labelledby="purchases-heading">
    <h2 id="purchases-heading">Shop purchases</h2>
    @component('components.layout.accordion', ['id' => 'support-shop-faq'])
        @component('components.layout.accordion-item', ['id' => 'support-shop-catalogs', 'title' => 'Where can I find eBooks and eScores?'])
            <p>Visit our <a href="{{ route('ebooks.index') }}">eBook catalog</a> and <a href="{{ route('escores.index') }}">eScore catalog</a> directly. The shop homepage is being prepared, but these catalogs and their checkout pages are available.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-shop-membership', 'title' => 'Is a shop purchase the same as a membership?'])
            <p>No. eBooks and eScores are separate products, with their own prices and details on each product page. Buying one does not start a PianoLIT membership. Check the product page for exactly what is included.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-download-help', 'title' => 'I need help with an order or download'])
            <p>Check your confirmation email, including your spam folder. If a download is missing or does not work, <a href="#contact-support">contact us</a> with the checkout email, product name, and purchase date. Include an order reference if you have one; please do not repurchase the item just to retry a download.</p>
        @endcomponent
    @endcomponent
</section>
