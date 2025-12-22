# Client Reminder App

A small business appointment + SMS reminder system.

## Setup

1. **Clone the repository**
2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```
3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Database**
   Configure your database credentials in `.env`.
   ```bash
   php artisan migrate
   ```
5. **Build Frontend**
   ```bash
   npm run build
   ```

## Configuration

### Queue & Scheduler

This application uses Laravel Queues and the Scheduler to send SMS reminders.

1. **Queue Configuration**
   Ensure your `.env` is set to use the database driver (or redis):
   ```
   QUEUE_CONNECTION=database
   ```
   
   Run the queue worker:
   ```bash
   php artisan queue:work
   ```

2. **Scheduler**
   The scheduler handles checking for upcoming appointments. Add the following Cron entry to your server:
   ```bash
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```
   
   Locally, you can run:
   ```bash
   php artisan schedule:work
   ```

## SMS Configuration
(To be documented in Phase 3)
