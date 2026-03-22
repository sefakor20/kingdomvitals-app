<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $templates = DB::table('email_templates')->get();

        foreach ($templates as $template) {
            $markdownBody = $this->htmlToMarkdown($template->body);

            DB::table('email_templates')
                ->where('id', $template->id)
                ->update(['body' => $markdownBody]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Converting back to HTML is complex and may lose formatting
        // The Markdown will render correctly anyway via Str::markdown()
    }

    /**
     * Convert HTML content to Markdown.
     */
    protected function htmlToMarkdown(string $html): string
    {
        $markdown = $html;

        // Convert headings
        $markdown = preg_replace('/<h1[^>]*>(.*?)<\/h1>/is', '# $1', $markdown);
        $markdown = preg_replace('/<h2[^>]*>(.*?)<\/h2>/is', '## $1', $markdown);
        $markdown = preg_replace('/<h3[^>]*>(.*?)<\/h3>/is', '### $1', $markdown);
        $markdown = preg_replace('/<h4[^>]*>(.*?)<\/h4>/is', '#### $1', $markdown);

        // Convert bold/strong
        $markdown = preg_replace('/<strong[^>]*>(.*?)<\/strong>/is', '**$1**', $markdown);
        $markdown = preg_replace('/<b[^>]*>(.*?)<\/b>/is', '**$1**', $markdown);

        // Convert italic/emphasis
        $markdown = preg_replace('/<em[^>]*>(.*?)<\/em>/is', '*$1*', $markdown);
        $markdown = preg_replace('/<i[^>]*>(.*?)<\/i>/is', '*$1*', $markdown);

        // Convert links
        $markdown = preg_replace('/<a[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '[$2]($1)', $markdown);

        // Convert line breaks
        $markdown = preg_replace('/<br\s*\/?>/i', "\n", $markdown);

        // Convert paragraphs
        $markdown = preg_replace('/<p[^>]*>(.*?)<\/p>/is', "$1\n\n", $markdown);

        // Convert unordered lists
        $markdown = preg_replace('/<ul[^>]*>(.*?)<\/ul>/is', '$1', $markdown);
        $markdown = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "- $1\n", $markdown);

        // Convert ordered lists
        $markdown = preg_replace('/<ol[^>]*>(.*?)<\/ol>/is', '$1', $markdown);
        // Note: This simplifies ordered lists to unordered

        // Strip remaining HTML tags
        $markdown = strip_tags($markdown);

        // Clean up multiple newlines
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);

        // Trim whitespace
        $markdown = trim($markdown);

        return $markdown;
    }
};
