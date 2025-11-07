I'll provide you with comprehensive instructions for running the Laravel queue worker on cPanel
  hosting in production.

  cPanel Queue Worker Setup

  Since cPanel hosting doesn't allow you to keep long-running processes like php artisan queue:work        
  running continuously, here are the best solutions:

  Option 1: Cron Job Approach (Recommended for cPanel)

  This is the most reliable method for cPanel hosting. Instead of a continuously running worker, you'll    
   use a cron job that processes queued jobs every minute.

  Step 1: Create a cron job in cPanel

  1. Log into your cPanel
  2. Go to Cron Jobs
  3. Add a new cron job with these settings:

  Command:
  * * * * * cd /home/yourusername/public_html && php artisan queue:work --stop-when-empty --max-time=50    
   >> /dev/null 2>&1

  Explanation:
  - * * * * * = Run every minute
  - --stop-when-empty = Stop when no more jobs (prevents overlap)
  - --max-time=50 = Stop after 50 seconds (before next cron runs)
  - >> /dev/null 2>&1 = Suppress output

  Important: Replace /home/yourusername/public_html with your actual path. You can find it by running      
  pwd in SSH.

  Option 2: Artisan Schedule (Alternative)

  You can also use Laravel's built-in scheduler:

  Step 1: Add to routes/console.php:

  use Illuminate\Support\Facades\Schedule;

  Schedule::command('queue:work --stop-when-empty --max-time=50')
      ->everyMinute()
      ->withoutOverlapping();

  Step 2: Create ONE cron job in cPanel:

  * * * * * cd /home/yourusername/public_html && php artisan schedule:run >> /dev/null 2>&1

  This gives you more flexibility to add other scheduled tasks in the future.

  Option 3: Supervisor (If Available)

  Some cPanel hosts support Supervisor. Check with your hosting provider first.

  Create supervisor config file: /etc/supervisor/conf.d/whiziq-worker.conf

  [program:whiziq-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=php /home/yourusername/public_html/artisan queue:work --sleep=3 --tries=3 --max-time=3600        
  autostart=true
  autorestart=true
  stopasgroup=true
  killasgroup=true
  user=yourusername
  numprocs=2
  redirect_stderr=true
  stdout_logfile=/home/yourusername/public_html/storage/logs/worker.log
  stopwaitsecs=3600

  Then reload: sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start 
  whiziq-worker:*

  Monitoring & Error Handling

  1. Enable Failed Jobs Table:

  php artisan queue:failed-table
  php artisan migrate

  2. Add to your cron jobs for automatic retry:

  0 */1 * * * cd /home/yourusername/public_html && php artisan queue:retry all >> /dev/null 2>&1

  This retries failed jobs every hour.

  3. Monitor Queue Health:

  Create a simple monitoring endpoint in routes/web.php:

  Route::get('/admin/queue-status', function () {
      $jobsCount = DB::table('jobs')->count();
      $failedCount = DB::table('failed_jobs')->count();

      return response()->json([
          'pending_jobs' => $jobsCount,
          'failed_jobs' => $failedCount,
          'status' => $failedCount > 10 ? 'warning' : 'healthy'
      ]);
  })->middleware('auth');

  Production Configuration

  Update .env on production:

  QUEUE_CONNECTION=database

  # Optional: Set queue timeout
  QUEUE_TIMEOUT=90

  # Optional: Set max attempts
  QUEUE_MAX_ATTEMPTS=3

  Testing Your Setup

  1. After setting up the cron job, test it:

  # SSH into your server
  cd /home/yourusername/public_html

  # Manually trigger the cron command
  php artisan queue:work --stop-when-empty --max-time=50

  2. Check if jobs are processing:

  # View jobs table
  php artisan tinker
  >>> DB::table('jobs')->count()

  # View failed jobs
  >>> DB::table('failed_jobs')->count()

  3. Monitor logs:

  tail -f storage/logs/laravel.log

  Important Production Notes

  1. Cron runs every minute - Jobs will be processed within 60 seconds maximum
  2. For urgent jobs - The 60-second delay is acceptable for appointment confirmations
  3. If you need real-time processing - Consider upgrading to VPS with Supervisor
  4. Email delivery - With cron, emails send within 1 minute (still fast enough)

  Recommended Setup for WhizIQ

  Based on your appointment booking system, I recommend Option 1 (simple cron job):

  * * * * * cd /home/yourusername/public_html && php artisan queue:work --stop-when-empty --max-time=50    
   --tries=3 >> /dev/null 2>&1

  This ensures:
  - Appointment confirmation emails sent within 1 minute
  - Meeting links generated within 1 minute
  - No server overload
  - Automatic retry on failure (--tries=3)

  The user experience is still excellent - they see instant confirmation, and get their email within 60    
   seconds instead of 5-10 seconds. This is perfectly acceptable for appointment confirmations.


