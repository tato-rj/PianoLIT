<?php

namespace Tests\Review;

use App\Piece;
use App\Services\WebApp\{AccessPolicy, AccessPrompts};

class AccessPromptsTest extends ReviewTestCase
{
    public function test_default_checklists_describe_actual_upgrades_without_repeating_existing_premium_features()
    {
        $copy = app(AccessPrompts::class);
        $signup = $copy->benefits('signup');
        $this->assertCount(6, $signup);
        $this->assertSame('Unlimited search results', $signup[0]);
        $this->assertSame('Unlimited favorite folders & saved pieces', $signup[1]);
        $this->assertContains('Full videos, Synthesia & audio for the weekly free pick', $signup);
        $this->assertContains('Read & annotate the weekly free pick score', $signup);
        $this->assertContains('Preview 3 pieces in every collection', $signup);
        $this->assertContains('Recently viewed pieces & personal suggestions', $signup);
        $premium = $copy->benefits('premium');
        $this->assertCount(5, $premium);
        $this->assertContains('Full videos, Synthesia & audio for every piece', $premium);
        $this->assertContains('Read & annotate every score', $premium);
        $this->assertContains('Download & print public-domain scores', $premium);
        $this->assertContains('Every piece in every collection', $premium);
        $this->assertContains('Create eScores for your practice', $premium);
        $this->assertStringNotContainsString('favorite', implode(' ', $premium), 'Unlimited favorites already belong to registered accounts in the saved defaults.');
    }

    public function test_checklists_follow_saved_limits_and_annotation_dependencies()
    {
        $policy = app(AccessPolicy::class);
        $rules = array_replace_recursive($policy->snapshot()['rules'], [
            'account' => ['search' => ['registered' => 'limited'], 'folders' => ['registered' => 'limited'],
                'folder_pieces' => ['registered' => 'limited'], 'collections' => ['registered' => 'available']],
            'free_pick' => ['score' => ['registered' => 'blocked'], 'performance' => ['guest' => 'available'],
                'synthesia' => ['guest' => 'available'], 'audio' => ['guest' => 'available']],
            'regular' => ['performance' => ['member' => 'limited'], 'audio' => ['member' => 'blocked'], 'annotations' => ['member' => 'blocked']],
        ]);
        $this->assertTrue($policy->save($rules, 0, 1));
        $copy = app(AccessPrompts::class);
        $signup = implode(' | ', $copy->benefits('signup'));
        $this->assertStringNotContainsString('search', $signup);
        $this->assertStringContainsString('Up to 2 favorite folders, with up to 5 pieces in each', $signup);
        $this->assertStringNotContainsString('weekly free pick', $signup, 'Signup cannot advertise existing media access or annotations without readable scores.');
        $this->assertStringContainsString('Premium lets you', $copy->prompt('annotations', 'signup', new Piece(['is_free' => true]))['description']);
        $premium = implode(' | ', $copy->benefits('premium'));
        $this->assertStringContainsString('Unlimited favorite folders & saved pieces', $premium);
        $this->assertStringContainsString('Full Synthesia for every piece', $premium);
        $this->assertStringNotContainsString('Full videos', $premium);
        $this->assertStringNotContainsString('Full audio', $premium);
        $this->assertStringNotContainsString('annotate every score', $premium);
        $this->assertStringNotContainsString('every collection', $premium);
        $this->assertLessThanOrEqual(6, count($copy->benefits('premium')));
    }

    public function test_signup_dialogs_share_body_titles_user_icons_free_actions_and_checklists()
    {
        $copy = app(AccessPrompts::class);
        foreach ([false, true] as $free) {
            $piece = new Piece(['is_free' => $free]);
            $html = view('webapp.components.signup-prompts', compact('piece'))->render();
            $dom = new \DOMDocument; @$dom->loadHTML($html); $xpath = new \DOMXPath($dom);
            foreach (['video', 'audio', 'score', 'annotations', 'score-export', 'favorites', 'escore', 'collections', 'search'] as $action) {
                $modal = $xpath->query('//*[@id="signup-'.$action.'-modal"]')->item(0);
                $this->assertNotNull($modal);
                $heading = $xpath->query('.//*[@id="'.$modal->getAttribute('aria-labelledby').'"]', $modal)->item(0);
                $this->assertSame('h2', $heading->nodeName);
                $this->assertSame(1, $xpath->query('ancestor::*[contains(@class,"modal-body")]', $heading)->length);
                $this->assertLessThanOrEqual(30, mb_strlen($heading->textContent));
                $this->assertSame(1, $xpath->query('.//*[@data-lucide-name="user"]', $modal)->length);
                $this->assertSame(6, $xpath->query('.//ul[@aria-label="Free account benefits"]/li', $modal)->length);
                $this->assertSame(6, $xpath->query('.//ul//i[@class="app-icon icon-check me-0"]', $modal)->length);
                $description = $xpath->query('.//*[@id="'.$modal->getAttribute('aria-describedby').'"]', $modal)->item(0);
                $this->assertStringContainsString('Create your free PianoLIT account', $description->textContent);
                $this->assertSame(1, $xpath->query('.//strong[text()="free"]', $description)->length);
                $this->assertSame('Sign up free', trim($xpath->query('.//a[contains(@class,"btn-primary")]', $modal)->item(0)->textContent));
            }
            $this->assertSame($free ? 'Unlock the score' : 'Explore more scores', $copy->prompt('score', 'signup', $piece)['title']);
            if ($directory = getenv('ACCESS_PROMPTS_PREVIEW_DIR')) file_put_contents($directory.'/'.($free ? 'free-pick' : 'regular').'-signup.html', $html);
        }
    }

    public function test_premium_dialog_uses_the_same_component_with_action_copy_and_crown()
    {
        $copy = app(AccessPrompts::class);
        $html = view('components.access-modal', $copy->prompt('default', 'premium') + ['id' => 'piece-upgrade-modal', 'actionCopies' => $copy->premiumCopy()])->render();
        $dom = new \DOMDocument; @$dom->loadHTML($html); $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//*[@data-lucide-name="crown"]')->length);
        $this->assertSame(5, $xpath->query('//ul[@aria-label="Premium benefits"]/li')->length);
        $this->assertSame('Go Premium', trim($xpath->query('//a[contains(@class,"btn-primary")]')->item(0)->textContent));
        foreach ($copy->premiumCopy() as $action) $this->assertLessThanOrEqual(30, mb_strlen($action['title']));
        if ($directory = getenv('ACCESS_PROMPTS_PREVIEW_DIR')) {
            file_put_contents($directory.'/premium.html', $html);
            file_put_contents($directory.'/inline-score.html', view('components.signup-card', $copy->prompt('score', 'signup', new Piece(['is_free' => true])) + ['headingId' => 'score-signup-title'])->render());
        }
    }
}
