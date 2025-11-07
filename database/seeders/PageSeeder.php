<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user as the author
        $admin = User::admin()->first();

        if (! $admin) {
            $this->command->warn('No admin user found. Please create an admin user first.');
            return;
        }

        // Privacy Policy
        Page::updateOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => $this->getPrivacyPolicyContent(),
                'meta_description' => 'Our privacy policy explains how we collect, use, and protect your personal information.',
                'meta_keywords' => 'privacy policy, data protection, personal information, GDPR',
                'is_published' => true,
                'published_at' => now(),
                'author_id' => $admin->id,
                'page_type' => 'policy',
                'sort_order' => 1,
            ]
        );

        $this->command->info('Privacy Policy page created/updated.');

        // Terms of Service
        Page::updateOrCreate(
            ['slug' => 'terms-of-service'],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => $this->getTermsOfServiceContent(),
                'meta_description' => 'Terms and conditions for using our service.',
                'meta_keywords' => 'terms of service, terms and conditions, user agreement',
                'is_published' => true,
                'published_at' => now(),
                'author_id' => $admin->id,
                'page_type' => 'legal',
                'sort_order' => 2,
            ]
        );

        $this->command->info('Terms of Service page created/updated.');

        // Cookie Policy
        Page::updateOrCreate(
            ['slug' => 'cookie-policy'],
            [
                'title' => 'Cookie Policy',
                'slug' => 'cookie-policy',
                'content' => $this->getCookiePolicyContent(),
                'meta_description' => 'Information about how we use cookies on our website.',
                'meta_keywords' => 'cookie policy, cookies, tracking, analytics',
                'is_published' => true,
                'published_at' => now(),
                'author_id' => $admin->id,
                'page_type' => 'policy',
                'sort_order' => 3,
            ]
        );

        $this->command->info('Cookie Policy page created/updated.');

        $this->command->info('All pages seeded successfully! You can now edit them in the admin panel.');
    }

    private function getPrivacyPolicyContent(): string
    {
        $appName = config('app.name', 'WhizIQ');
        $appUrl = config('app.url', '');
        $supportEmail = config('app.support_email', '');

        return <<<HTML
<p class="mb-6">
    {$appName} ("us", "we", or "our") operates the {$appUrl} website (the "Service").
    This page informs you of our policies regarding the collection, use, and disclosure of personal data when you use our Service and the choices you have associated with that data.
</p>

<p class="mb-6">
    We will not use or share your information with anyone except as described in this Privacy Policy.
</p>

<p class="mb-6">
    We use your data to provide and improve the Service. By using the Service, you agree to the collection and use of information in accordance with this policy.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Information Collection And Use</h2>
<p class="mb-6">
    We collect several different types of information for various purposes to provide and improve our Service to you.
</p>

<h3 class="text-xl font-semibold mt-6 mb-3">Personal Data</h3>
<p class="mb-6">
    While using our Service, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you ("Personal Data"). Personally identifiable information may include, but is not limited to:
</p>
<ul class="list-disc ml-6 mb-6">
    <li>Email address</li>
    <li>First and last name</li>
    <li>Cookies and usage data</li>
</ul>

<h3 class="text-xl font-semibold mt-6 mb-3">Usage Data</h3>
<p class="mb-6">
    We may also collect information on how the Service is accessed and used ("Usage Data"). This Usage Data may include information such as your computer's Internet Protocol address (e.g. IP address), browser type, browser version, the pages of our Service that you visit, the time and date of your visit, the time spent on those pages, unique device identifiers and other diagnostic data.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Use of Data</h2>
<p class="mb-6">{$appName} uses the collected data for various purposes:</p>
<ul class="list-disc ml-6 mb-6">
    <li>To provide and maintain our Service</li>
    <li>To notify you about changes to our Service</li>
    <li>To provide customer support</li>
    <li>To gather analysis or valuable information so that we can improve our Service</li>
    <li>To monitor the usage of our Service</li>
    <li>To detect, prevent and address technical issues</li>
</ul>

<h2 class="text-2xl font-bold mt-8 mb-4">Security Of Data</h2>
<p class="mb-6">
    The security of your data is important to us, but remember that no method of transmission over the Internet, or method of electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your Personal Data, we cannot guarantee its absolute security.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Your Rights</h2>
<p class="mb-6">
    {$appName} aims to take reasonable steps to allow you to correct, amend, delete, or limit the use of your Personal Data.
</p>
<p class="mb-6">
    In certain circumstances, you have the right:
</p>
<ul class="list-disc ml-6 mb-6">
    <li>To access and receive a copy of the Personal Data we hold about you</li>
    <li>To rectify any Personal Data held about you that is inaccurate</li>
    <li>To request the deletion of Personal Data held about you</li>
</ul>

<h2 class="text-2xl font-bold mt-8 mb-4">Contact Us</h2>
<p class="mb-6">
    If you have any questions about this Privacy Policy, please contact us at <a href="mailto:{$supportEmail}" class="text-blue-600 hover:underline">{$supportEmail}</a>.
</p>
HTML;
    }

    private function getTermsOfServiceContent(): string
    {
        $appName = config('app.name', 'WhizIQ');
        $appUrl = config('app.url', '');
        $supportEmail = config('app.support_email', '');

        return <<<HTML
<p class="mb-6">
    Please read these Terms of Service ("Terms", "Terms of Service") carefully before using the {$appUrl} website (the "Service") operated by {$appName} ("us", "we", or "our").
</p>

<p class="mb-6">
    Your access to and use of the Service is conditioned on your acceptance of and compliance with these Terms. These Terms apply to all visitors, users and others who access or use the Service.
</p>

<p class="mb-6">
    By accessing or using the Service you agree to be bound by these Terms. If you disagree with any part of the terms then you may not access the Service.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Accounts</h2>
<p class="mb-6">
    When you create an account with us, you must provide us information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our Service.
</p>
<p class="mb-6">
    You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your password, whether your password is with our Service or a third-party service.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Subscriptions</h2>
<p class="mb-6">
    Some parts of the Service are billed on a subscription basis ("Subscription(s)"). You will be billed in advance on a recurring and periodic basis ("Billing Cycle"). Billing cycles are set either on a monthly or annual basis, depending on the type of subscription plan you select when purchasing a Subscription.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Intellectual Property</h2>
<p class="mb-6">
    The Service and its original content, features and functionality are and will remain the exclusive property of {$appName} and its licensors. The Service is protected by copyright, trademark, and other laws of both the United States and foreign countries.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Links To Other Web Sites</h2>
<p class="mb-6">
    Our Service may contain links to third-party web sites or services that are not owned or controlled by {$appName}.
</p>
<p class="mb-6">
    {$appName} has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third party web sites or services.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Termination</h2>
<p class="mb-6">
    We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.
</p>
<p class="mb-6">
    Upon termination, your right to use the Service will immediately cease. If you wish to terminate your account, you may simply discontinue using the Service.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Limitation Of Liability</h2>
<p class="mb-6">
    In no event shall {$appName}, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your access to or use of or inability to access or use the Service.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Changes</h2>
<p class="mb-6">
    We reserve the right, at our sole discretion, to modify or replace these Terms at any time. What constitutes a material change will be determined at our sole discretion.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Contact Us</h2>
<p class="mb-6">
    If you have any questions about these Terms, please contact us at <a href="mailto:{$supportEmail}" class="text-blue-600 hover:underline">{$supportEmail}</a>.
</p>
HTML;
    }

    private function getCookiePolicyContent(): string
    {
        $appName = config('app.name', 'WhizIQ');

        return <<<HTML
<p class="mb-6">
    This Cookie Policy explains how {$appName} ("we", "us", and "our") uses cookies and similar technologies to recognize you when you visit our website. It explains what these technologies are and why we use them, as well as your rights to control our use of them.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">What are cookies?</h2>
<p class="mb-6">
    Cookies are small data files that are placed on your computer or mobile device when you visit a website. Cookies are widely used by website owners in order to make their websites work, or to work more efficiently, as well as to provide reporting information.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Why do we use cookies?</h2>
<p class="mb-6">
    We use first party and third party cookies for several reasons. Some cookies are required for technical reasons in order for our Service to operate, and we refer to these as "essential" or "strictly necessary" cookies.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Types of cookies we use</h2>

<h3 class="text-xl font-semibold mt-6 mb-3">Essential Cookies</h3>
<p class="mb-6">
    These cookies are strictly necessary to provide you with services available through our Service and to use some of its features, such as access to secure areas.
</p>

<h3 class="text-xl font-semibold mt-6 mb-3">Performance and Functionality Cookies</h3>
<p class="mb-6">
    These cookies are used to enhance the performance and functionality of our Service but are non-essential to their use. However, without these cookies, certain functionality may become unavailable.
</p>

<h3 class="text-xl font-semibold mt-6 mb-3">Analytics and Customization Cookies</h3>
<p class="mb-6">
    These cookies collect information that is used either in aggregate form to help us understand how our Service is being used or how effective our marketing campaigns are, or to help us customize our Service for you.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">How can you control cookies?</h2>
<p class="mb-6">
    You have the right to decide whether to accept or reject cookies. You can exercise your cookie preferences by clicking on the appropriate opt-out links provided in the cookie banner.
</p>
<p class="mb-6">
    You can also set or amend your web browser controls to accept or refuse cookies. If you choose to reject cookies, you may still use our Service though your access to some functionality and areas of our Service may be restricted.
</p>

<h2 class="text-2xl font-bold mt-8 mb-4">Updates to this Cookie Policy</h2>
<p class="mb-6">
    We may update this Cookie Policy from time to time in order to reflect changes to the cookies we use or for other operational, legal or regulatory reasons. Please therefore re-visit this Cookie Policy regularly to stay informed about our use of cookies and related technologies.
</p>
HTML;
    }
}
