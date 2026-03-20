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

        $this->generatedPrompt = "### 🔹 ROLE DEFINITION
You are a Senior Product Designer and SaaS Architect with deep expertise in building scalable platforms, modern dashboards, and high-conversion user experiences. You specialize in creating industry-leading software systems that solve real-world business problems through clean UI/UX and robust technical architecture.

### 🔹 CORE OBJECTIVE
Build a high-end, production-ready SaaS platform for \"{$model->name}\" categorized under \"{$model->category}\". The goal is to automate operations, manage growth, and provide actionable insights for a business based in {$model->city}.

### 🔹 PRODUCT VISION
- **Problem:** Fragmented operations and lack of integrated digital scaling tools for businesses in the {$model->category} sector.
- **Target Users:** Business owners, operational managers, and high-value customers.
- **Value Proposition:** A unified command center that leverages AI to drive revenue and streamline service delivery.

### 🔹 DESIGN PRINCIPLES
- Clean, minimalist UI with a focus on data hierarchy.
- Mobile-first responsiveness and accessibility.
- SaaS-level quality with seamless transitions and micro-interactions.
- Scalable design tokens for typography, spacing, and color systems.

### 🔹 LAYOUT STRUCTURE
- **Sidebar:** Collapsible navigation with quick-action shortcuts.
- **Navbar:** Global search, notifications, user profile, and context-aware Breadcrumbs.
- **Content Area:** Dynamic grid-based layout for dashboards and modules.

### 🔹 CORE FEATURES
- **Unified Dashboard:** Real-time business health monitoring.
- **Lead/Customer Management:** Advanced CRM for the {$model->category} niche.
- **Analytics Engine:** Visualizing growth, ratings (Current: {$model->rating}), and market trends.
- **Automated Payments:** Subscription management and invoicing.
- **System Settings:** Multi-tenant configuration and integration hub.

### 🔹 CORE SCREENS (DETAILED)
- **Main Dashboard:** KPI cards (Revenue, Leads, Reviews), Activity Feed, and Growth Charts.
- **Module Management:** Category-specific workflows (e.g., booking, inventory, or service tracking).
- **Deep Analytics:** Customer behavior reports and ROI tracking.
- **Billing & Payments:** Transaction history, payout status, and plan management.
- **Team & Security:** Role-based access control and audit logs.

### 🔹 UI COMPONENTS
- **Tables:** Sortable, filterable grids with bulk actions and export.
- **Forms:** Multi-step wizards with real-time validation.
- **Modals:** Contextual overlays for quick edits and confirmations.
- **Badges:** Status indicators (Success, Warning, Info, Danger).
- **Notifications:** In-app toasts and notification center.

### 🔹 DESIGN STYLE
- **SaaS Modern:** Light/Dark mode support using a sleek slate/indigo palette.
- **Typography:** Inter or Outfit font family for modern readability.
- **Spacing:** Strict 4px/8px grid system for perfect alignment.

### 🔹 AI FEATURES
- **Smart Email Generation:** AI-driven client outreach and follow-ups.
- **Automation workflows:** Trigger-based actions for customer lifecycle.
- **Predictive Insights:** Data-backed recommendations for revenue growth.

### 🔹 DATABASE DESIGN
- **Users:** Schema for auth, profiles, and preferences.
- **Businesses:** Multi-tenant structure for {$model->name} metadata.
- **Leads/Customers:** Relational tables for relationship tracking.
- **Analytics:** Optimized tables for time-series data and events.
- **Payments:** Secure ledger for transactions and plans.

### 🔹 API DESIGN
- RESTful endpoints for all core modules.
- JWT-based authentication and rate-limiting.
- Webhook support for third-party integrations (Stripe, Twilio, etc.).

### 🔹 AUTOMATION
- Background jobs for heavy processing (data scraping, bulk emails).
- Event-driven workflows for real-time notifications.

### 🔹 MONETIZATION
- Three-tier pricing (Starter, Professional, Enterprise).
- Feature-gating based on subscription status.
- Add-on marketplace for specialized AI tools.

### 🔹 SCALABILITY
- Modular architecture (Service Classes, Repository Patterns).
- Horizontal scaling capability with queue systems (Redis/RabbitMQ).
- Multi-region database support for global businesses.

### 🔹 OUTPUT REQUIREMENTS
- Full UI mockups and frontend code structure.
- Documented backend architecture.
- Production-ready deployment strategy.

### 🔹 CONSTRAINTS
- No generic, cookie-cutter templates.
- No basic design elements; must feel like a premium $10k+ SaaS product.
- Must incorporate business-specific data points: {$model->name} ({$model->website}).

### 🔹 FINAL INSTRUCTION
Design the complete system ensuring it is scalable, clean, and production-ready.";
    }

    public function render(): View
    {
        return view('livewire.detail-result', [
            'business' => $this->business,
        ])->layout('layouts.app', ['title' => 'Business Lead Details']);
    }
}
