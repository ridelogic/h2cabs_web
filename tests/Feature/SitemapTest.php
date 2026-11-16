<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_sitemap_is_served_as_xml(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_sitemap_is_well_formed_and_lists_configured_routes(): void
    {
        $xml = simplexml_load_string($this->get(route('sitemap'))->getContent());

        $this->assertNotFalse($xml, 'Sitemap is not well-formed XML.');
        $this->assertSame('urlset', $xml->getName());

        $locations = [];
        foreach ($xml->url as $url) {
            $locations[] = (string) $url->loc;
        }

        $this->assertCount(count(config('site.sitemap_routes')), $locations);
        $this->assertContains(route('home'), $locations);
    }

    public function test_sitemap_lists_the_public_pages_but_not_private_ones(): void
    {
        $xml = $this->get(route('sitemap'))->getContent();

        foreach (['home', 'about', 'services', 'destinations', 'booking.create', 'contact'] as $route) {
            $this->assertStringContainsString('<loc>'.route($route).'</loc>', $xml);
        }

        $this->assertStringNotContainsString(route('booking.confirmation'), $xml);
        $this->assertStringNotContainsString(route('booking.estimate'), $xml);
        $this->assertStringNotContainsString(route('places.search'), $xml);
        $this->assertStringNotContainsString('/admin', $xml);
    }

    public function test_sitemap_starts_with_xml_declaration(): void
    {
        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8"?>',
            $this->get(route('sitemap'))->getContent()
        );
    }
}
