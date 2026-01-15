<x-layouts.app>
    <div class="flex min-h-[60vh] items-center justify-center px-4 py-12">
        <div class="max-w-md text-center">
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                <flux:icon name="lock-closed" class="h-10 w-10 text-amber-600 dark:text-amber-400" />
            </div>

            <flux:heading size="xl" class="mb-4">
                {{ __('Upgrade Required') }}
            </flux:heading>

            <flux:text class="mb-8 text-zinc-600 dark:text-zinc-400">
                {{ __('The :module feature is not available on your current plan. Upgrade to access this and other premium features.', ['module' => $moduleName]) }}
            </flux:text>

            <div class="space-y-3">
                <flux:button variant="primary" class="w-full" icon="arrow-up-circle">
                    {{ __('View Available Plans') }}
                </flux:button>

                <flux:button variant="ghost" href="{{ route('dashboard') }}" class="w-full">
                    {{ __('Return to Dashboard') }}
                </flux:button>
            </div>

            <div class="mt-8 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Need help choosing a plan? Contact our support team for personalized recommendations.') }}
                </flux:text>
            </div>
        </div>
    </div>
</x-layouts.app>
