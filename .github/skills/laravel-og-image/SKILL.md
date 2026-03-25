---
name: laravel-og-image
description: Generate Open Graph images from Blade views using spatie/laravel-og-image. Covers defining OG image templates, fallback images, customizing screenshots, pre-generating images, and configuring drivers (Browsershot, Cloudflare).
---

# Laravel OG Image

## When to use this skill

Use this skill when the user needs to generate Open Graph images in a Laravel application using `spatie/laravel-og-image`. This includes defining OG image templates in Blade views, setting up fallback images, customizing screenshot options (size, format, quality), pre-generating images, clearing generated images, and configuring drivers.

## Defining OG image templates

Place the `<x-og-image>` component anywhere in a Blade view:

```blade
<x-og-image>
    <div class="w-full h-full bg-blue-900 text-white flex items-center justify-center">
        <h1 class="text-6xl font-bold">{{ $post->title }}</h1>
    </div>
</x-og-image>
```

The component outputs a hidden `<template>` tag. The package middleware automatically injects `og:image`, `twitter:image`, and `twitter:card` meta tags into the `<head>`. No manual meta tags needed.

### Using a Blade view

Reference a Blade view instead of inline HTML:

```blade
<x-og-image view="og-image.post" :data="['title' => $post->title, 'author' => $post->author->name]" />
```

The view receives the `data` array as its variables.

### Custom dimensions

Override the default 1200x630 size per component. Both width and height must be provided together:

```blade
<x-og-image :width="800" :height="400">
    <div>This OG image will be 800x400</div>
</x-og-image>
```

### Image format

Override the default JPEG format per component:

```blade
<x-og-image format="webp">
    <div>WebP format</div>
</x-og-image>
```

## Fallback images

Register a fallback for pages without an `<x-og-image>` component:

```php
use Illuminate\Http\Request;
use Spatie\OgImage\Facades\OgImage;

public function boot(): void
{
    OgImage::fallbackUsing(function (Request $request) {
        return view('og-image.fallback', [
            'title' => config('app.name'),
        ]);
    });
}
```

Return `null` from the closure to skip the fallback for specific requests:

```php
OgImage::fallbackUsing(function (Request $request) {
    if ($request->routeIs('admin.*')) {
        return null;
    }

    return view('og-image.fallback', ['title' => config('app.name')]);
});
```

## Configuring screenshots

All configuration is done via the `OgImage` facade in `AppServiceProvider`:

```php
use Spatie\OgImage\Facades\OgImage;

OgImage::format('webp')
    ->size(1200, 630)
    ->disk('s3', 'og-images');
```

### Using Cloudflare instead of Browsershot

```php
OgImage::useCloudflare(
    apiToken: env('CLOUDFLARE_API_TOKEN'),
    accountId: env('CLOUDFLARE_ACCOUNT_ID'),
);
```

### Configuring the screenshot builder

For full control, use `configureScreenshot()`:

```php
use Spatie\LaravelScreenshot\Enums\WaitUntil;
use Spatie\LaravelScreenshot\ScreenshotBuilder;

OgImage::configureScreenshot(function (ScreenshotBuilder $screenshot) {
    $screenshot->waitUntil(WaitUntil::NetworkIdle0);
});
```

## Pre-generating images

Generate images ahead of time instead of lazily on first crawler request:

```php
use Spatie\OgImage\Facades\OgImage;

$imageUrl = OgImage::generateForUrl('https://yourapp.com/blog/my-post');
```

Via artisan:

```bash
php artisan og-image:generate https://yourapp.com/page1 https://yourapp.com/page2
```

## Clearing generated images

```bash
php artisan og-image:clear
```

## Customizing the page URL

By default, query parameters are stripped from the cached URL. To include them:

```php
OgImage::resolveScreenshotUrlUsing(function (Request $request) {
    return $request->fullUrl();
});
```

## Customizing actions

Override action classes in `config/og-image.php`:

```php
'actions' => [
    'generate_og_image' => \App\Actions\CustomGenerateOgImageAction::class,
    'inject_og_image_fallback' => \Spatie\OgImage\Actions\InjectOgImageFallbackAction::class,
    'render_og_image_screenshot' => \Spatie\OgImage\Actions\RenderOgImageScreenshotAction::class,
],
```

Custom action classes must extend the default action.

## Previewing

Append `?ogimage` to any page URL to see exactly what will be screenshotted. This renders just the template content at the configured dimensions using the page's full `<head>`.

## Design tips

- Design for 1200x630 pixels (the default size)
- Use `w-full h-full` on the root element to fill the viewport
- Use `flex` or `grid` for layout
- Keep text large, since OG images are viewed as thumbnails

## Do and Don't

Do:
- Remove existing `og:image`, `twitter:image`, and `twitter:card` meta tags from views. The package injects these automatically.
- Use `w-full h-full` on the root element inside `<x-og-image>`.
- Preview with `?ogimage` before deploying.
- Pre-generate images for high-traffic pages.
- Provide both `width` and `height` together when using custom dimensions.

Don't:
- Don't add `og:image` or `twitter:image` meta tags manually. The middleware handles this.
- Don't provide only `width` or only `height` on the component. Both are required together.
- Don't put `<x-og-image>` inside a layout that is used by multiple pages unless the content varies per page (otherwise all pages get the same OG image hash).