<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Response;

/**
 * The sitemap, so Google can find the vacancies at all.
 *
 * JobPosting markup only helps once a crawler reaches the page. There was no
 * sitemap and no reference to one in robots.txt, so discovery relied on Google
 * happening to follow a link.
 *
 * Generated rather than stored: a stale file listing closed vacancies is worse
 * than none, because Google penalises a sitemap full of dead URLs.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'freq' => 'daily'],
            ['loc' => url('/jobs'), 'priority' => '0.9', 'freq' => 'hourly'],
        ];

        // Only live vacancies. A closed job in a sitemap is a soft 404 to a
        // crawler and drags the whole domain's crawl budget down.
        Job::where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('closing_date')->orWhereDate('closing_date', '>=', today());
            })
            ->orderByDesc('published_at')
            ->limit(5000)
            ->get(['id', 'updated_at'])
            ->each(function (Job $job) use (&$urls) {
                $urls[] = [
                    'loc' => route('jobs.show', $job->id),
                    'lastmod' => $job->updated_at?->toAtomString(),
                    'priority' => '0.8',
                    'freq' => 'daily',
                ];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>'."\n";
            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'."\n";
            }
            $xml .= '    <changefreq>'.$url['freq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$url['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
