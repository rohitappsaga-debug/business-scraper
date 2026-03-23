<?php

namespace App\Livewire;

use App\Models\Business;
use Illuminate\View\View;
use Livewire\Component;

class DetailResult extends Component
{
    public int $id = 0;

    public ?string $generatedPrompt = null;

    /** @var array<string, mixed> */
    public array $business = [];

    public function mount(int $id): void
    {
        $this->id = $id;

        $model = Business::with('businessEmails')->find($id);

        if (! $model) {
            $this->business = [];

            return;
        }

        $emails = $model->businessEmails->pluck('email')->toArray();
        $primaryEmail = $model->email ?? ($emails[0] ?? '');

        $this->business = [
            'id' => $model->id,
            'name' => $model->name,
            'category' => $model->category ?? 'General Business',
            'description' => $model->getGeneratedDescription(),
            'address' => trim(implode(', ', array_filter([
                $model->address,
                $model->city,
                $model->state,
                $model->getEffectiveCountry(),
            ]))),
            'city_state' => trim(implode(', ', array_filter([$model->city, $model->state]))),
            'postal_country' => trim(implode(', ', array_filter([$model->zip, $model->getEffectiveCountry()]))),
            'phone' => $model->phone ?? 'Not available',
            'email' => $primaryEmail,
            'website' => $model->website ?? 'Not available',
            'website_url' => ! empty($model->website)
                ? (str_starts_with((string) $model->website, 'http') ? $model->website : 'https://'.$model->website)
                : null,
            'maps_url' => 'https://maps.google.com/?q='.urlencode(
                trim(implode(', ', array_filter([$model->address, $model->city, $model->state])))
            ),
            'rating' => $model->rating ? number_format((float) $model->rating, 1) : 'N/A',
            'review_count' => $model->reviews_count ?? 0,
            'status' => 'Active',
            'verification' => 'Scraped',
            'email_status' => ! empty($primaryEmail) ? 'Found' : 'Searching...',
            'last_updated' => $model->updated_at?->format('d M Y, H:i'),
            'hours' => $model->hours ?? [],
            'social' => $model->social ?? [],
            'all_emails' => $emails,
            'source' => $model->source,
        ];
    }

    public function backToResults(): void
    {
        $this->redirectRoute('result');
    }

    public function exportLead(): void
    {
        session()->flash('message', 'Lead exported successfully.');
    }

    public function openGoogleMaps(): void
    {
        if (! empty($this->business['maps_url'])) {
            $this->dispatch('open-url', url: $this->business['maps_url']);
        }
    }

    public function generateMasterPrompt(): void
    {
        $model = Business::find($this->id);

        if (! $model) {
            return;
        }

        $this->generatedPrompt = "1️⃣ ROLE DEFINITION
You are a Senior SaaS Product Designer and Conversion-Focused UI/UX Expert specializing in building premium, high-converting marketing websites for industry-leading SaaS platforms.

2️⃣ CORE OBJECTIVE
- **Goal:** Design a high-end, conversion-optimized SaaS landing page for \"{$model->name}\".
- **Target Audience:** Potential customers and clients looking for top-tier services in the \"{$model->category}\" sector.
- **Conversion Goal:** Drive primary actions like \"Start Free Trial\", \"Book a Demo\", or \"Get Started\".

3️⃣ DESIGN STYLE
- **SaaS Aesthetics:** Clean, minimal, and premium (Stripe/Notion/Linear level).
- **Typography:** Strong, modern sans-serif hierarchy (e.g., Inter, Outfit, or Manrope).
- **Visuals:** Strategic use of whitespace, smooth gradients, and subtle micro-animations.
- **Color Palette:** A sophisticated modern palette tailored to the {$model->category} industry.

4️⃣ PAGE STRUCTURE
- **Hero Section:** High-impact headline for {$model->name}, persuasive subheadline, primary/secondary CTAs, and a premium visual (product mockup or abstract graphic).
- **Social Proof:** \"Trusted by\" logo cloud, user stats, or key achievements ($model->rating/5 stars based on {$model->reviews_count} reviews).
- **Features Section:** 4–6 key features highlighting the specialized {$model->category} solutions.
- **Product Showcase:** Visual workflows or UI previews demonstrating the value in {$model->city}.
- **Benefits Section:** Value-driven propositions explaining \"Why Choose Us?\".
- **Pricing Section:** 2–3 transparent tiers (Starter, Pro, Enterprise) with feature lists.
- **Testimonials:** High-trust customer quotes with avatars and credentials.
- **FAQ Section:** Smart accordion for common questions about {$model->name} services.
- **Final CTA:** A bold, high-contrast section to drive the final conversion.
- **Footer:** Organized links, social icons, newsletter sign-up, and branding.

5️⃣ NAVBAR DESIGN
- **Logo:** Clean branding for {$model->name}.
- **Menu:** Features, Solutions, Pricing, Resources.
- **Actions:** Login (Ghost Button) and \"Get Started\" (Primary CTA).

6️⃣ UI COMPONENTS
- Glassmorphic Hero card
- Interactive Feature cards with hover effects
- Sleek Pricing cards with \"Most Popular\" highlight
- Clean Testimonial masonry or slider
- Elegant FAQ accordion
- High-visibility CTA banners

7️⃣ UX REQUIREMENTS
- Clear and frequent CTA placement (Top, Mid, Bottom)
- Strong visual hierarchy focusing on the value proposition
- Frictionless conversion flow
- Logical content progression to build trust

8️⃣ RESPONSIVENESS
- Mobile-first approach
- Fully responsive layout for all breakpoints
- Functional mobile menu (Hamburger/Side-drawer)

9️⃣ CONSTRAINTS
- ❌ DO NOT generate admin dashboards or panels
- ❌ DO NOT generate backend systems or internal UI
- ❌ DO NOT create complex data tables or SaaS panels
- Avoid clutter; keep it breathable and premium

🔟 SUCCESS CRITERIA
- Looks like a world-class SaaS marketing website
- Clearly communicates the value of \"{$model->name}\" in {$model->city}
- High conversion potential through persuasive UI/UX
- Production-ready design logic

1️⃣1️⃣ FINAL INSTRUCTION
Design a complete SaaS Landing Page UI that is modern, clean, conversion-focused, and production-ready.";
    }

    public function render(): View
    {
        return view('livewire.detail-result', [
            'business' => $this->business,
        ])->layout('layouts.app', ['title' => 'Business Lead Details']);
    }
}
