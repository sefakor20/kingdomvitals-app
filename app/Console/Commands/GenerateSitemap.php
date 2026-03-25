<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

#[Signature('sitemap:generate')]
#[Description('Generate the sitemap.xml file for SEO')]
class GenerateSitemap extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating sitemap...');

        Sitemap::create()
            ->add(
                Url::create('https://kingdomvitals.app')
                    ->setPriority(1.0)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            )
            ->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully at public/sitemap.xml');

        return self::SUCCESS;
    }
}
