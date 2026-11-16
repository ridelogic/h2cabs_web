<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    public function test_homepage_outputs_default_title_and_description(): void
    {
        $this->get(route('home'))
            ->assertSee('<title>'.e(config('site.seo.default_title')).'</title>', false)
            ->assertSee('<meta name="description" content="'.e(config('site.seo.default_description')).'">', false);
    }

    public function test_canonical_points_to_home_route_without_query_string(): void
    {
        $html = $this->get(route('home', ['utm_source' => 'test']))->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.route('home').'">', $html);
        $this->assertSame(1, substr_count($html, 'rel="canonical"'));
    }

    public function test_open_graph_and_twitter_tags_are_each_output_once(): void
    {
        $html = $this->get(route('home'))->getContent();

        foreach (['og:title', 'og:description', 'og:url', 'og:image', 'og:type', 'og:site_name', 'og:locale'] as $property) {
            $this->assertSame(1, substr_count($html, 'property="'.$property.'"'), "Expected one {$property} tag.");
        }

        foreach (['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'] as $name) {
            $this->assertSame(1, substr_count($html, 'name="'.$name.'"'), "Expected one {$name} tag.");
        }

        $this->assertSame(1, substr_count($html, '<title>'));
        $this->assertSame(1, substr_count($html, 'name="description"'));
    }

    public function test_og_image_is_an_absolute_url_to_an_existing_file(): void
    {
        $this->get(route('home'))
            ->assertSee('<meta property="og:image" content="'.asset(config('site.seo.default_image')).'">', false);

        $this->assertFileExists(public_path(config('site.seo.default_image')));
    }

    public function test_non_production_environments_are_not_indexable(): void
    {
        config(['site.seo.indexable' => false]);

        $this->get(route('home'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_production_is_indexable(): void
    {
        config(['site.seo.indexable' => true]);

        $this->get(route('home'))->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_organization_structured_data_is_valid_json_without_unconfigured_fields(): void
    {
        config(['site.contact.phone' => null, 'site.contact.email' => null]);

        $html = $this->get(route('home'))->getContent();

        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches));

        $data = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('TaxiService', $data['@type']);
        $this->assertSame('H2 CABS', $data['name']);
        $this->assertSame(route('home'), $data['url']);
        $this->assertArrayNotHasKey('telephone', $data);
        $this->assertArrayNotHasKey('email', $data);
    }

    public function test_structured_data_includes_configured_phone(): void
    {
        config(['site.contact.phone' => '+91 90000 00000']);

        $html = $this->get(route('home'))->getContent();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        $this->assertSame('+91 90000 00000', json_decode($matches[1], true)['telephone']);
    }
}
