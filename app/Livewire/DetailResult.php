<?php

namespace App\Livewire;

use App\Models\Business;
use App\Services\SocialLinkService;
use Illuminate\View\View;
use Livewire\Attributes\On;
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

    public function getBusinessType(string $category): string
    {
        $category = strtolower($category);

        if (str_contains($category, 'hospital') || str_contains($category, 'clinic') || str_contains($category, 'doctor') || str_contains($category, 'health') || str_contains($category, 'medical') || str_contains($category, 'dentist')) {
            return 'Healthcare';
        }

        if (str_contains($category, 'restaurant') || str_contains($category, 'cafe') || str_contains($category, 'food') || str_contains($category, 'dining') || str_contains($category, 'bakery')) {
            return 'Restaurant';
        }

        if (str_contains($category, 'education') || str_contains($category, 'school') || str_contains($category, 'university') || str_contains($category, 'college') || str_contains($category, 'academy') || str_contains($category, 'training')) {
            return 'Education';
        }

        if (str_contains($category, 'corporate') || str_contains($category, 'company') || str_contains($category, 'business') || str_contains($category, 'agency') || str_contains($category, 'consulting') || str_contains($category, 'legal') || str_contains($category, 'finance')) {
            return 'Corporate';
        }

        if (str_contains($category, 'coach') || str_contains($category, 'influencer') || str_contains($category, 'personal') || str_contains($category, 'speaker') || str_contains($category, 'author')) {
            return 'Personal Brand';
        }

        if (str_contains($category, 'lifestyle') || str_contains($category, 'gym') || str_contains($category, 'spa') || str_contains($category, 'beauty') || str_contains($category, 'fashion') || str_contains($category, 'travel')) {
            return 'Lifestyle';
        }

        return 'Corporate'; // Default
    }

    public function generateMasterPrompt(): void
    {
        $model = Business::find($this->id);

        if (! $model) {
            return;
        }

        $businessType = $this->getBusinessType($model->category ?? 'Business');
        $industryLogic = $this->getIndustrySpecificLogic($businessType, $model);

        $this->generatedPrompt = "1️⃣ ROLE DEFINITION
You are an Elite AI Product Designer, Senior Business Brand Strategist, and Industry-Specific UX Architect. You specialize in creating high-prestige, conversion-optimized landing pages for top-tier {$businessType} entities.

2️⃣ CORE OBJECTIVE
- **Goal:** Design a world-class, premium landing page for \"{$model->name}\".
- **Business Identity:** A leading \"{$model->category}\" provider in {$model->city}.
- **Context:** {$model->getGeneratedDescription()}
- **Target:** {$industryLogic['target']}

3️⃣ DESIGN STYLE
- **Aesthetic:** {$industryLogic['aesthetic']}
- **Typography:** Masterful pairing of high-end fonts (e.g., Serif for headings, Sans-serif for body).
- **Vibe:** Premium, standard-setting, and highly authoritative.

4️⃣ PAGE STRUCTURE (DYNAMIC ⚠️)
{$industryLogic['structure']}

5️⃣ IMAGE GENERATION REQUIREMENTS
Generate photorealistic, high-end visuals representing:
{$industryLogic['images']}
- Style: Consistent \"Editorial Photography\" with shallow depth, cinematic lighting, and professional grading.

6️⃣ UI COMPONENTS
- {$industryLogic['components']}
- Bespoke Hero sections
- Interactive service/product grids
- High-trust social proof widgets

7️⃣ UX REQUIREMENTS
- conversion-focused visual hierarchy
- Seamless micro-interactions
- Absolute focus on industry-specific trust signals

8️⃣ RESPONSIVENESS
- Flawless Mobile-first execution
- Retaining premium atmosphere across all screen sizes

9️⃣ CONSTRAINTS
- ❌ NO SaaS or generic 'Startup' UI
- ❌ NO administrative dashboards
- ❌ NO generic stock imagery templates
- ❌ NO cluttered layouts

🔟 SUCCESS CRITERIA
- Looks like a \$25,000+ custom-coded site
- Perfectly matches the {$businessType} industry standards
- Built for maximum authority and trust

1️⃣1️⃣ FINAL INSTRUCTION
Design a complete {$businessType} landing page UI for \"{$model->name}\" that is industry-specific, premium, and production-ready.";
    }

    private function getIndustrySpecificLogic(string $type, Business $model): array
    {
        $city = $model->city ?? 'your city';

        return match ($type) {
            'Healthcare' => [
                'target' => 'Building deep patient trust and facilitating easy appointment scheduling.',
                'aesthetic' => 'Trust-focused, ultra-clean, serene, and professional. Use sterile but warm color palettes (soft blues, whites, teal).',
                'structure' => "- Hero: Expertise and Compassion\n- Our Doctors: Prestige medical staff profiles\n- Treatments & Specializations: Detailed medical services\n- Patient Success Stories: High-trust testimonials\n- Book Appointment: Seamless booking flow\n- Clinic Technology: Showcasing modern facilities",
                'images' => "- Close-up of empathetic healthcare professionals in {$city}\n- Modern, high-tech clinic interiors\n- Subtle medical equipment with soft lighting",
                'components' => 'Appointment booking widgets, Treatment cards, Doctor profiles',
            ],
            'Restaurant' => [
                'target' => 'Sensory appeal and immediate reservation conversion.',
                'aesthetic' => 'Visual-heavy, atmospheric, and appetizing. Use rich textures, deep blacks, or warm earthy tones depending on the cuisine.',
                'structure' => "- Hero: The Signature Dish experience\n- Our Story / Chef: The culinary philosophy\n- The Menu: Interactive, high-end dish browser\n- Atmospheric Gallery: Interior and vibe showcase\n- Reservations: Urgent and elegant CTA\n- Location & Private Dining: Event focus",
                'images' => "- Cinematic food photography of signature dishes\n- Atmospheric interior shots of the restaurant in {$city}\n- Lifestyle shots of happy patrons dining",
                'components' => 'Interactive menus, Reservation forms, Food sliders',
            ],
            'Education' => [
                'target' => 'Academic authority and streamlined admission enrollment.',
                'aesthetic' => 'Structured, organized, academic, and inspiring. Use prestigious blues, deep maroons, or clean scholarly whites.',
                'structure' => "- Hero: The Future of Learning\n- Academic Programs / Courses: Filterable program list\n- Meet the Faculty: Authority profiles\n- Campus / Lab Showcase: Facility focus\n- Admissions Path: Step-by-step enrollment\n- Student/Alumni Success: Social evidence",
                'images' => "- Modern classroom or lab environments\n- Engaged faculty members mentoring students\n- Professional shots of the campus in {$city}",
                'components' => 'Course browsers, Admissions timelines, Faculty cards',
            ],
            'Corporate' => [
                'target' => 'Professional authority and high-value lead capture.',
                'aesthetic' => 'Clean, modern, architectural, and efficient. Use corporate blues, grays, and high-impact white space.',
                'structure' => "- Hero: The Value Proposition\n- Our Services / Expertise: B2B focus\n- Case Studies / Success Stories: Demonstrated results\n- Leadership Team: Corporate structure\n- Insight / Blog: Thought leadership\n- Global Partners / Clients: Logo clouds of trust",
                'images' => "- Sleek office architecture and collaborative spaces\n- Professional B2B interactions\n- Abstract representations of growth and strategy",
                'components' => 'Service portfolios, Client logo grids, Content hubs',
            ],
            'Personal Brand' => [
                'target' => 'Building authority and personal connection for high-end coaching/consulting.',
                'aesthetic' => 'Authority-driven, bold, and personality-focused. Use personal colors, high-key photography, and bold typography.',
                'structure' => "- Hero: The One-on-One transformation\n- Profile / My Journey: Narrative focus\n- Expertise Pillars: What I teach/do\n- Elite Testimonials: Proof of transformation\n- Offerings / Masterclasses: Direct conversion\n- Daily Insights: Authentic content",
                'images' => "- High-quality portraits of the brand owner\n- Speaking engagements or workshops\n- Lifestyle shots reflecting the brand philosophy",
                'components' => 'Testimonial carousels, Course/Webinar cards, Personal story timelines',
            ],
            'Lifestyle' => [
                'target' => 'Aspirational lifestyle appeal and service bookings.',
                'aesthetic' => 'Aspirational, vibrant, and sleek. Use trendy colors, soft shadows, and dynamic layouts.',
                'structure' => "- Hero: Living the Best Life\n- Experiences / Services: Aspirational focus\n- Visual Gallery: Lifestyle showcase\n- Customer Experiences: Vibe-focused reviews\n- Book Your Experience: Easy booking\n- Community / Social: Real-time vibe",
                'images' => "- High-energy lifestyle shots\n- Spa/Gym/Travel environments in {$city}\n- Minimalist product/service staging",
                'components' => 'Galleries, Booking widgets, Experience cards',
            ],
            default => [
                'target' => 'General business growth and customer trust.',
                'aesthetic' => 'Professional, balanced, and modern.',
                'structure' => "- Hero: What we do\n- Services: How we help\n- About Us: Who we are\n- Social Proof: Why trust us\n- Contact: Work with us",
                'images' => "- Professional business environment\n- Team at work\n- Customer service focus",
                'components' => 'Contact forms, Service grids, Trust badges',
            ],
        };
    }

    public function confirmLogout(): void
    {
        $this->dispatch('open-confirm-modal', [
            'title' => 'Logout',
            'message' => 'Are you sure you want to logout of your session?',
            'confirmButton' => 'Logout',
            'type' => 'danger',
            'confirmActionUrl' => route('logout'),
        ]);
    }

    #[On('logout')]
    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('login');
    }

    public function render(): View
    {
        return view('livewire.detail-result', [
            'business' => $this->business,
        ])->layout('layouts.app', ['title' => 'Business Lead Details']);
    }
}
