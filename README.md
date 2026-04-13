# Business Scraper - Fresh Setup Guide

Follow these steps to set up the project on a new machine.

## 📋 Prerequisites
- **PHP**: ^8.2 (Enabled extensions: `curl`, `mbstring`, `pdo_mysql`, `xml`, `zip`)
- **Node.js & NPM**: (Required for Playwright scraping)
- **Composer**: For PHP dependencies

---

## 🚀 Fresh Setup (Steps)

Run these commands in order from your project root:

1. **Install Dependencies & Environment**:
   ```bash
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Database & Browsers (CRITICAL)**:
   ```bash
   php artisan migrate
   npx playwright install chromium
   ```
   *Note: The scraper will fail if `playwright install` is skipped.*

3. **Build Frontend**:
   ```bash
   npm run build
   ```

---

## ⚡ Running the Application

To run the **web server and scraper queue** at the same time:

```bash
composer dev
```

> [!IMPORTANT]
> The scraper runs in the background. If you do not run `composer dev` (or `php artisan queue:work`), the scraping tasks will stay "Pending" and never finish.

---

## 🔍 Quick Troubleshooting
- **Scraper not working?** Run `npx playwright install chromium`.
- **Tasks stuck at Pending?** Ensure `php artisan queue:work` is running.
- **Database issues?** Ensure your DB credentials in `.env` match your WAMP/XAMPP settings.
