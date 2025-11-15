# Cron Jobs & Task Scheduling

## Laravel Scheduler

Laravel includes a task scheduler that runs scheduled tasks. The Docker setup includes a dedicated `scheduler` container.

## Docker Scheduler (Recommended)

The `scheduler` container in `docker-compose.yml` automatically runs Laravel's scheduler:

```yaml
scheduler:
  command: >
    sh -c "while true; do
      php artisan schedule:run --verbose --no-interaction &
      sleep 60
    done"
```

This checks for scheduled tasks every minute and runs them automatically.

### View Scheduler Logs

```bash
docker-compose logs -f scheduler
```

### Restart Scheduler

```bash
docker-compose restart scheduler
```

## Defining Scheduled Tasks

Edit `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Run every minute
    $schedule->command('queue:work --stop-when-empty')
        ->everyMinute()
        ->withoutOverlapping();

    // Daily at 2 AM
    $schedule->command('backup:run')
        ->daily()
        ->at('02:00');

    // Weekly on Monday
    $schedule->command('reports:generate')
        ->weekly()
        ->mondays()
        ->at('09:00');

    // Every hour
    $schedule->command('campaigns:check')
        ->hourly();

    // Custom closure
    $schedule->call(function () {
        // Your custom logic
    })->daily();
}
```

## System Cron (Alternative)

If not using Docker scheduler, add to system crontab:

```bash
# Edit crontab
crontab -e

# Add Laravel scheduler (runs every minute)
* * * * * cd /var/www/phonehospital && docker-compose exec -T app php artisan schedule:run >> /dev/null 2>&1
```

Or without Docker:

```bash
* * * * * cd /var/www/phonehospital && php artisan schedule:run >> /dev/null 2>&1
```

## Recommended Scheduled Tasks

### 1. Queue Processing

```php
$schedule->command('queue:work --stop-when-empty --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
```

### 2. Database Backup

```php
$schedule->command('backup:run')
    ->daily()
    ->at('02:00');
```

### 3. Log Cleanup

```php
$schedule->command('log:clear')
    ->daily()
    ->at('03:00');
```

### 4. Campaign Status Check

```php
$schedule->call(function () {
    // Check and update campaign statuses
    Campaign::where('end_date', '<', now())
        ->where('status', 'active')
        ->update(['status' => 'expired']);
})->hourly();
```

### 5. Ticket Status Updates

```php
$schedule->call(function () {
    // Auto-update ticket statuses
    Ticket::where('status', 'new')
        ->where('created_at', '<', now()->subDays(7))
        ->update(['status' => 'expired']);
})->daily();
```

## Testing Scheduled Tasks

### Run Scheduler Manually

```bash
docker-compose exec app php artisan schedule:run
```

### List Scheduled Tasks

```bash
docker-compose exec app php artisan schedule:list
```

### Test Specific Task

```bash
# Run a specific command
docker-compose exec app php artisan your:command

# Run with verbose output
docker-compose exec app php artisan schedule:run -v
```

## Monitoring

### Check Scheduler Status

```bash
# View scheduler container status
docker-compose ps scheduler

# View logs
docker-compose logs scheduler | tail -50
```

### Task Execution Logs

Laravel logs scheduled task execution. Check logs:

```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

## Best Practices

1. **Use withoutOverlapping()** for long-running tasks
2. **Set appropriate timeouts** for tasks
3. **Log task execution** for debugging
4. **Handle failures gracefully** with try-catch
5. **Monitor task performance** regularly

## Example: Complete Scheduler Setup

```php
protected function schedule(Schedule $schedule): void
{
    // Queue processing
    $schedule->command('queue:work --stop-when-empty --tries=3')
        ->everyMinute()
        ->withoutOverlapping()
        ->runInBackground();

    // Database backup
    $schedule->command('backup:run')
        ->daily()
        ->at('02:00')
        ->onFailure(function () {
            // Send notification on failure
        });

    // Log cleanup
    $schedule->command('log:clear')
        ->daily()
        ->at('03:00');

    // Campaign status updates
    $schedule->call(function () {
        \App\Models\Campaign::where('end_date', '<', now())
            ->where('status', 'active')
            ->update(['status' => 'expired']);
    })->hourly();

    // Ticket reminders
    $schedule->call(function () {
        // Send reminders for pending tickets
    })->daily()
      ->at('10:00');
}
```


