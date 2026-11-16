<?php

namespace Tests\Unit;

use App\Support\SeoMeta;
use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    public function test_defaults_come_from_config(): void
    {
        $seo = SeoMeta::make();

        $this->assertSame(config('site.seo.default_title'), $seo->title);
        $this->assertSame(config('site.seo.default_description'), $seo->description);
        $this->assertSame(asset(config('site.seo.default_image')), $seo->image);
        $this->assertSame('website', $seo->type);
    }

    public function test_page_title_gets_brand_suffix(): void
    {
        $this->assertSame('Airport Transfers | H2 CABS', SeoMeta::make(['title' => 'Airport Transfers'])->title);
    }

    public function test_page_can_opt_out_of_indexing_even_in_production(): void
    {
        config(['site.seo.indexable' => true]);

        $this->assertSame('index, follow', SeoMeta::make()->robots());
        $this->assertSame('noindex, nofollow', SeoMeta::make(['indexable' => false])->robots());
    }
}
