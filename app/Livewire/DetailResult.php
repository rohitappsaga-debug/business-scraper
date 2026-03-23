<?php

namespace App\Livewire;

use App\Models\Business;
use App\Services\SocialLinkService;
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

        $socialLinkService = app(SocialLinkService::class);
        $socialLinks = $socialLinkService->getLinksForBusiness($model);

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
            'social' => $socialLinks,
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
You are a Senior Business Brand Strategist and Ultra-Premium UI/UX Designer specializing in creating standard-setting, high-prestige marketing websites for elite businesses. Your expertise lies in translating a brand's physical excellence into a digital masterpiece that commands authority and drives conversion.

2️⃣ CORE OBJECTIVE
- **Goal:** Design an ultra-premium, world-class landing page for \"{$model->name}\".
- **Business Identity:** A leading \"{$model->category}\" professional service provider.
- **Context:** {$model->getGeneratedDescription()}
- **Conversion Goal:** Secure high-value inquiries, bookings, or consultations.

3️⃣ DESIGN STYLE (ULTRA-PREMIUM)
- **Aesthetics:** Sophisticated, timeless, and standard-setting. Avoid generic trends; focus on bespoke luxury and professional authority.
- **Typography:** Masterful pairing of premium serifs for headings and clean, high-legibility sans-serifs for body text (e.g., Playfair Display with Montserrat, or Chrono with Inter).
- **Visuals:** High-fidelity textures, elegant depth, and cinematic lighting in all UI elements.
- **Color Palette:** A curated, high-contrast palette that reflects the excellence of the {$model->category} industry in {$model->city}.

4️⃣ PAGE STRUCTURE (PRESTIGE FOCUS)
- **Hero Section:** A cinematic introduction with a powerful value statement for {$model->name}, clear high-end CTAs, and a breathtaking visual showcase.
- **The Prestige Section:** Focused on the unique \"Standard of Excellence\" provided by the business.
- **Services Portfolio:** Detailed breakdown of premium services offered, tailored to the {$model->category} niche.
- **Proof of Excellence:** Strategic display of the business's stellar reputation ($model->rating/5 stars based on {$model->reviews_count} verified reviews).
- **Work & Product Showcase:** A high-end gallery or slider featuring the business's actual work and products.
- **Investment / Packages:** Clear, transparent, and professionally presented tiers or service packages.
- **Client Testimonials:** High-impact social proof from elite clientele.
- **Experience FAQ:** Addressing sophisticated client concerns with professional clarity.
- **Final Call to Action:** A prestige-focused invitation to collaborate or book.
- **Global Footer:** Comprehensive navigation, branding, and contact details with architectural symmetry.

5️⃣ IMAGE GENERATION REQUIREMENTS (CRITICAL)
Generate high-quality, photorealistic images that represent the business's work, environment, and products:
- **Project Portfolios:** Cinematic shots of completed work relevant to the \"{$model->category}\" industry.
- **Product Photography:** Macro and lifestyle shots of the products offered by {$model->name}, using studio lighting and premium staging.
- **Business Environment:** High-end interior or exterior shots of a business located in {$model->city}, capturing the professional atmosphere.
- **Style:** Use a consistent \"Premium Photography\" style with shallow depth of field, natural yet enhanced lighting, and a sophisticated color grade.

6️⃣ UI COMPONENTS
- Bespoke Hero layouts with custom masking
- Interactive Portfolio cards with fluid transitions
- Elegant Service tiles with rich iconography
- Modern, clean Pricing/Package tables
- High-trust Review widgets
- Seamless, architectural Navbar and Footer

7️⃣ UX REQUIREMENTS
- Flawless visual hierarchy that guides the user through the brand story
- Strategic placement of primary and secondary actions
- Smooth, meaningful animations that enhance perceived value
- Absolute focus on clarity, trust, and professional standard

8️⃣ RESPONSIVENESS
- Flawless adaptation across all modern devices
- Retaining premium feel and layout integrity on mobile
- Optimized touch interactions for high-end mobile experiences

9️⃣ CONSTRAINTS
- ❌ NO \"SaaS\" or \"Start-up\" terminology; this is a professional business entity.
- ❌ NO admin dashboards, data tables, or backend UI.
- ❌ NO cluttered or busy layouts; maintain a breathable, elite atmosphere.
- ❌ NO generic stock-photo feel; aim for bespoke, high-end photography logic.

🔟 SUCCESS CRITERIA
- The design looks like a \$20k+ custom-coded business website.
- It clearly positions \"{$model->name}\" as the market leader in {$model->city}.
- Every element screams \"Quality\" and \"Standard\".

1️⃣1️⃣ FINAL INSTRUCTION
Design a complete, premium Business Landing Page UI that is modern, clean, standard-setting, and production-ready.";
    }

    public function render(): View
    {
        return view('livewire.detail-result', [
            'business' => $this->business,
        ])->layout('layouts.app', ['title' => 'Business Lead Details']);
    }
}
