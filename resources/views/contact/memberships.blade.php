<section id="memberships" class="support-section" aria-labelledby="memberships-heading">
    <h2 id="memberships-heading">Memberships & billing</h2>
    <p>Your membership is managed where you purchased it, even if you use PianoLIT on another device. Using the website on an iPhone does not make it an App Store subscription.</p>
    <div class="row g-3 mt-1 mb-4">
        <div class="col-md-6">
            <div class="card border rounded p-4 h-100">
                <div class="d-flex align-items-center mb-3">@icon('pianolit', ['size' => 'lg'])<h3 class="h5 mb-0">Purchased on the website</h3></div>
                <p class="text-muted">Manage your plan and payments in the web app.</p>
                <ol class="ps-3 mb-4">
                    <li>Sign in to your PianoLIT account.</li>
                    <li>Open <strong>Settings → My membership</strong>.</li>
                    <li>Use <strong>Change plan</strong>, <strong>Billing</strong>, or <strong>Invoices</strong> for what you need.</li>
                </ol>
                <p>To cancel, choose <strong>Billing → Cancel membership</strong> and complete the confirmation.</p>
                <p class="small text-muted">Your access continues until the end date shown there. Cancel before your web trial ends to avoid the first payment.</p>
                <a class="btn btn-secondary mt-auto align-self-start" href="{{ route('webapp.membership.edit') }}">My membership @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border rounded p-4 h-100">
                <div class="d-flex align-items-center mb-3">@icon('brand-apple', ['size' => 'lg'])<h3 class="h5 mb-0">Purchased in the iOS app</h3></div>
                <p class="text-muted">Apple manages your App Store subscription.</p>
                <ol class="ps-3 mb-4">
                    <li>Open <strong>Settings</strong> on your iPhone or iPad.</li>
                    <li>Tap <strong>your name → Subscriptions → PianoLIT</strong>.</li>
                    <li>Review available plans or tap <strong>Cancel Subscription</strong> and follow Apple’s prompts.</li>
                </ol>
                <p>If there is no Cancel button and an expiration date is shown, Apple indicates it is already canceled.</p>
                <p class="small text-muted">Apple advises canceling a trial at least 24 hours before it ends. Check the access dates Apple shows.</p>
                <a class="btn btn-secondary mt-auto align-self-start" href="https://account.apple.com/account/manage/section/subscriptions" target="_blank" rel="noopener noreferrer">Manage with Apple @icon('arrow-up-right', ['mr' => 0, 'ml' => 2])</a>
            </div>
        </div>
    </div>
    <p class="small text-muted">Not sure who bills you? Check your receipt or <a href="{{ route('webapp.membership.edit') }}">My membership</a>. An Apple receipt means Apple handles billing. Deleting the app or signing out does not cancel a subscription.</p>
    <p class="small mb-4"><a href="https://support.apple.com/en-us/118428" target="_blank" rel="noopener noreferrer">Apple’s cancellation guide</a></p>

    @component('components.layout.accordion', ['id' => 'support-billing-faq'])
        @component('components.layout.accordion-item', ['id' => 'support-renewals', 'title' => 'When will I be charged?'])
            <p>Memberships renew automatically unless canceled. Your checkout shows the price, billing interval, and any trial offered. For web billing, check your next date in <a href="{{ route('webapp.membership.edit') }}">My membership</a>; for App Store billing, check Apple’s subscription settings. Follow the cancellation steps above before renewal if you do not want to continue.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-change-plan', 'title' => 'Can I change my plan or payment method?'])
            <p>For web subscriptions, use <a href="{{ route('webapp.membership.edit') }}">My membership</a>: plan changes take effect at renewal, and Billing lets you update your card. For App Store subscriptions, use Apple’s subscription settings for available plans and your Apple Account payment settings for payment methods.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-missing-subscription', 'title' => 'Why can’t I find PianoLIT in my Apple subscriptions?'])
            <p>Check which Apple Account appears on your purchase receipt. It may differ from your PianoLIT email. If you purchased on the website, manage billing in <a href="{{ route('webapp.membership.edit') }}">My membership</a> instead. If you are still unsure, <a href="#contact-support">contact us</a> before subscribing again.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-paid-access', 'title' => 'I paid, but my membership isn’t showing up'])
            <p>First, sign in with the PianoLIT account used for your membership. For an App Store purchase, also check that your device uses the Apple Account on your receipt and look for a restore-purchases option in the app.</p>
            <p>See <a href="https://support.apple.com/en-us/108096" target="_blank" rel="noopener noreferrer">Apple’s restore-purchase guide</a>. If access is still missing, send us your account email and purchase date. Please contact us before buying another subscription.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-refund', 'title' => 'I have an unexpected charge or need a refund'])
            <p>For a website payment, <a href="#contact-support">email PianoLIT support</a> with the date and amount. For an App Store payment, <a href="https://reportaproblem.apple.com" target="_blank" rel="noopener noreferrer">request a refund from Apple</a>; Apple reviews eligibility. A refund request and subscription cancellation are separate steps.</p>
            <p>If you think you have both web and Apple subscriptions, tell us so we can help you identify them.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-devices', 'title' => 'Can I use my membership on the web and in the iOS app?'])
            <p>Sign in with the same PianoLIT account to use your existing membership. Features can differ between the two apps. If access does not match, contact us before starting a second subscription. Changing devices does not change who bills you.</p>
        @endcomponent
        @component('components.layout.accordion-item', ['id' => 'support-switch-provider', 'title' => 'Can I switch between web and Apple billing?'])
            <p>Contact us first so we can check your existing membership and help you avoid overlapping charges. A web subscription does not automatically cancel or replace an App Store subscription, or vice versa.</p>
        @endcomponent
    @endcomponent
</section>
