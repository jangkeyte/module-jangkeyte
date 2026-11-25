<?php

namespace JangKeyte\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use JangKeyte\Database\Seeders\JangKeyteDatabaseSeeder;
use JangKeyte\Providers\JangKeyteServiceProvider;
use JsonException;
use stdClass;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class InstallCommand extends Command
{
    protected $signature = 'jangkeyte:install {--without-seed : Skip seeding default roles, permissions and admin accounts}';

    protected $description = 'Install JangKeyte Core Admin, RBAC Authentication, Assets, and run initial setup';

    public function handle(): int
    {
        $this->components->info('Installing JangKeyte Modular Core & RBAC v2.1.0...');

        // 1. Publish config & AdminLTE assets
        $this->call('vendor:publish', [
            '--provider' => JangKeyteServiceProvider::class,
            '--tag' => 'jangkeyte-config',
        ]);

        if (File::isDirectory(dirname(__DIR__, 3) . '/resources/assets')) {
            $this->call('vendor:publish', [
                '--provider' => JangKeyteServiceProvider::class,
                '--tag' => 'jangkeyte-assets',
            ]);
        }

        // Ensure default upload directories & placeholder avatar exist
        $defaultImage = public_path('assets/images/default.png');
        foreach (['users', 'post', 'banner', 'activity'] as $uploadSubDir) {
            $dir = public_path('storage/uploads/' . $uploadSubDir);
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            if (File::isFile($defaultImage) && ! File::isFile($dir . '/default.png')) {
                File::copy($defaultImage, $dir . '/default.png');
            }
        }

        // 2. Ensure modules directory & modules_statuses.json exist
        $modulesDir = config('jangkeyte.modules_path', base_path('modules'));
        if (! File::isDirectory($modulesDir)) {
            File::makeDirectory($modulesDir, 0755, true);
        }

        $statusesFile = config('jangkeyte.statuses_file', base_path('modules_statuses.json'));
        if (! File::isFile($statusesFile)) {
            File::put($statusesFile, "{}\n");
        }

        // 3. Ensure host composer.json has PSR-4 mapping for Modules\ and regenerate autoloader
        if ($this->ensureModulesAutoload()) {
            $this->runComposerDumpAutoload();
        }

        // 4. Ensure config/auth.php uses JangKeyte\Models\User::class if still pointing to default App\Models\User
        $authConfigPath = config_path('auth.php');
        if (File::isFile($authConfigPath)) {
            $authContent = File::get($authConfigPath);
            $updated = preg_replace(
                "/(App\\\\Models\\\\User::class|Modules\\\\Authetication\\\\src\\\\Models\\\\User::class)/",
                'JangKeyte\\Models\\User::class',
                $authContent
            );
            if ($updated !== null && $updated !== $authContent) {
                File::put($authConfigPath, $updated);
                $this->components->task('Updated auth user provider model to JangKeyte\Models\User::class');
            }
        }

        // 5. Run database migrations
        $this->components->task('Running database migrations', function (): void {
            Artisan::call('migrate', ['--force' => true]);
        });

        // 6. Seed default Roles, Permissions, and Admin accounts
        if (! $this->option('without-seed')) {
            $this->components->task('Seeding default Roles, Permissions & Admin User', function (): void {
                Artisan::call('db:seed', [
                    '--class' => JangKeyteDatabaseSeeder::class,
                    '--force' => true,
                ]);
            });
        }

        $this->newLine();
        $this->components->info('JangKeyte v2.1.0 installed successfully! Default login: admin / 123456');

        return self::SUCCESS;
    }

    protected function ensureModulesAutoload(): bool
    {
        $composerPath = base_path('composer.json');

        if (! File::isFile($composerPath)) {
            $this->components->warn("composer.json not found at [{$composerPath}]. Skipping PSR-4 autoload configuration.");

            return false;
        }

        try {
            $composer = json_decode(File::get($composerPath), false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->components->warn("Unable to parse composer.json ({$e->getMessage()}). Skipping PSR-4 autoload configuration.");

            return false;
        }

        if (! $composer instanceof stdClass) {
            $this->components->warn('composer.json root must be a JSON object. Skipping PSR-4 autoload configuration.');

            return false;
        }

        if (isset($composer->autoload)) {
            if (! $composer->autoload instanceof stdClass) {
                $this->components->warn('composer.json "autoload" must be a JSON object. Skipping PSR-4 autoload configuration.');

                return false;
            }
        } else {
            $composer->autoload = new stdClass();
        }

        if (isset($composer->autoload->{'psr-4'})) {
            if (! $composer->autoload->{'psr-4'} instanceof stdClass) {
                $this->components->warn('composer.json "autoload.psr-4" must be a JSON object. Skipping PSR-4 autoload configuration.');

                return false;
            }
        } else {
            $composer->autoload->{'psr-4'} = new stdClass();
        }

        $psr4 = $composer->autoload->{'psr-4'};

        if (! property_exists($psr4, 'Modules\\')) {
            $psr4->{'Modules\\'} = 'modules/';

            $encoded = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                $this->components->warn('Unable to encode updated composer.json. Skipping PSR-4 autoload configuration.');

                return false;
            }

            File::put($composerPath, $encoded . PHP_EOL);
            $this->components->task('Added "Modules\\\\": "modules/" to composer.json autoload.psr-4');
        }

        return true;
    }

    protected function runComposerDumpAutoload(): void
    {
        $composerBinary = (new ExecutableFinder())->find('composer');

        if ($composerBinary !== null) {
            $command = [$composerBinary, 'dump-autoload'];
        } elseif (File::isFile(base_path('composer.phar'))) {
            $command = [PHP_BINARY, base_path('composer.phar'), 'dump-autoload'];
        } else {
            $this->components->warn('Composer executable not found. Please run `composer dump-autoload` manually.');

            return;
        }

        $errorOutput = null;

        $this->components->task('Running composer dump-autoload', function () use ($command, &$errorOutput): bool {
            try {
                $process = new Process($command, base_path(), timeout: 120);
                $process->run();

                if (! $process->isSuccessful()) {
                    $errorOutput = trim($process->getErrorOutput() ?: $process->getOutput());

                    return false;
                }

                return true;
            } catch (Throwable $e) {
                $errorOutput = $e->getMessage();

                return false;
            }
        });

        if ($errorOutput !== null) {
            $details = $errorOutput !== '' ? " ({$errorOutput})" : '';
            $this->components->warn("Unable to run composer dump-autoload automatically{$details}. Please run `composer dump-autoload` manually.");
        }
    }
}
