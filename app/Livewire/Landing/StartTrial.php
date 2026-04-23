<?php

declare(strict_types=1);

namespace App\Livewire\Landing;

use App\Models\Domain;
use App\Models\TrialSignup;
use App\Services\TenantCreationService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.landing')]
class StartTrial extends Component
{
    private const SUBDOMAIN_REGEX = '/^[a-z0-9](?:[a-z0-9-]{1,28}[a-z0-9])?$/';

    private const RESERVED = [
        'www', 'admin', 'api', 'app', 'mail', 'smtp', 'imap', 'pop', 'support',
        'help', 'status', 'staging', 'dev', 'test', 'demo', 'docs', 'blog',
        'login', 'register', 'signup', 'auth', 'dashboard', 'superadmin',
        'tenant', 'tenants', 'public', 'assets', 'cdn', 'static', 'billing', 'pay',
    ];

    public string $churchName = '';

    public string $subdomain = '';

    public string $adminName = '';

    public string $adminEmail = '';

    public string $contactEmail = '';

    public string $contactPhone = '';

    public string $address = '';

    public bool $submitted = false;

    public ?string $createdDomain = null;

    public ?string $subdomainStatus = null;

    #[Computed]
    public function parentDomain(): string
    {
        $host = strtolower(request()->getHost());

        if (str_ends_with($host, 'kingdomvitals.app')) {
            return 'kingdomvitals.app';
        }

        return 'kingdomvitals-app.test';
    }

    public function updatedSubdomain(): void
    {
        $this->subdomain = strtolower(trim($this->subdomain));

        if ($this->subdomain === '') {
            $this->subdomainStatus = null;

            return;
        }

        if (in_array($this->subdomain, self::RESERVED, true)) {
            $this->subdomainStatus = 'reserved';

            return;
        }

        if (! preg_match(self::SUBDOMAIN_REGEX, $this->subdomain)) {
            $this->subdomainStatus = 'invalid';

            return;
        }

        $fqdn = $this->subdomain.'.'.$this->parentDomain();

        $this->subdomainStatus = Domain::where('domain', $fqdn)->exists()
            ? 'taken'
            : 'available';
    }

    public function submit(TenantCreationService $service): void
    {
        $ipKey = 'trial-signup:'.request()->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $this->addError('churchName', 'Too many attempts. Please try again in an hour.');

            return;
        }

        $this->validate([
            'churchName' => 'required|string|min:2|max:120',
            'subdomain' => ['required', 'string', 'min:3', 'max:30', 'regex:'.self::SUBDOMAIN_REGEX, Rule::notIn(self::RESERVED)],
            'adminName' => 'required|string|min:2|max:120',
            'adminEmail' => ['required', 'email:rfc', 'max:190', Rule::unique('mysql.trial_signups', 'email')],
            'contactEmail' => 'nullable|email:rfc|max:190',
            'contactPhone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
        ]);

        $fqdn = $this->subdomain.'.'.$this->parentDomain();

        if (Domain::where('domain', $fqdn)->exists()) {
            $this->subdomainStatus = 'taken';
            $this->addError('subdomain', 'Just taken — please choose another.');

            return;
        }

        RateLimiter::hit($ipKey, 3600);

        try {
            DB::connection('mysql')->transaction(function () use ($service, $fqdn): void {
                $signup = TrialSignup::create([
                    'email' => $this->adminEmail,
                    'ip_address' => request()->ip(),
                    'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                ]);

                $tenant = $service->createTenantWithAdmin(
                    [
                        'name' => $this->churchName,
                        'domain' => $fqdn,
                        'contact_email' => $this->contactEmail !== '' ? $this->contactEmail : $this->adminEmail,
                        'contact_phone' => $this->contactPhone !== '' ? $this->contactPhone : null,
                        'address' => $this->address !== '' ? $this->address : null,
                        'trial_days' => 14,
                    ],
                    [
                        'name' => $this->adminName,
                        'email' => $this->adminEmail,
                    ],
                );

                $signup->update(['tenant_id' => $tenant->id]);

                $this->createdDomain = $fqdn;
            });
        } catch (UniqueConstraintViolationException) {
            $this->addError('adminEmail', 'This email has already started a trial.');

            return;
        } catch (\Throwable $e) {
            Log::error('Trial signup failed', ['error' => $e->getMessage(), 'email' => $this->adminEmail]);
            $this->addError('churchName', 'We could not start your trial. Please try again or contact support.');

            return;
        }

        $this->submitted = true;
    }

    public function render(): Factory|View
    {
        return view('livewire.landing.start-trial');
    }
}
