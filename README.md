# Installation Guide - Business Scraper

This guide will help you set up and run the Business Scraper project on your local machine.

## Prerequisites

Ensure you have the following installed on your system:
- **PHP**: ^8.2 (Required for Laravel 12)
- **Composer**: For managing PHP dependencies
- **Node.js & NPM**: For frontend assets (Vite, Tailwind CSS)
- **SQLite**: (Default database engine)

## Quick Start (Automated Installation)

The project includes a built-in setup script that handles dependency installation, environment setup, and database migrations.

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd business-scraper
   ```

2. **Run the setup command**:
   ```bash
   composer setup
   ```
   *This command will install Composer/NPM packages, create your `.env` file, generate an app key, and run migrations.*

---

## Manual Installation

If you prefer to perform the steps manually:

1. **Install PHP Dependencies**:
   ```bash
   composer install
   ```

2. **Set Up Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Initialization**:
   *Ensure `database/database.sqlite` exists if not created automatically:*
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

4. **Install and Build Frontend Assets**:
   ```bash
   npm install
   npm run build
   ```

---

## Core Scraper Dependencies

If you are setting up the scraping engine from scratch or need to reinstall key components, these are the primary packages used:

```bash
composer require spatie/crawler
composer require symfony/dom-crawler
composer require symfony/css-selector
composer require guzzlehttp/guzzle
composer require maatwebsite/excel
```

---

## Running the Application

To start the development environment (Server, Queue Worker, and Vite), use the following command:

```bash
composer dev
```

This command uses `concurrently` to run:
- **Web Server**: `php artisan serve`
- **Queue Worker**: `php artisan queue:listen`
- **Asset Watcher**: `npm run dev`
- **Log Monitor**: `php artisan pail`

---

## Scraper Usage

Once the application is running:
1. Navigate to the dashboard (standard `http://localhost:8000`).
2. Enter the search query and location for the businesses you want to scrape.
3. The scraper will process tasks in the background using Laravel Queues.

## Important Commands

- **Run Tests**: `composer test` or `php artisan test`
- **Format Code**: `vendor/bin/pint`
- **Check Paths**: `php artisan route:list`
