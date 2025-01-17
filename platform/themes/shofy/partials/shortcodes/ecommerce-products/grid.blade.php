@php
    $itemsPerRow = $shortcode->items_per_row ?: 4;
    $itemsPerRow = $shortcode->with_sidebar ? $itemsPerRow - 1 : $itemsPerRow;
@endphp

<section class="tp-product-arrival-area pt-30 pb-30">
    <div class="container">
        @if ($shortcode->title)
            <div class="mb-40">
                {!! Theme::partial('section-title', compact('shortcode')) !!}
            </div>
        @endif

        @if ($shortcode->with_sidebar)
            <div class="row">
                <div class="col-xl-4 col-lg-5">
                    @include(Theme::getThemeNamespace('partials.shortcodes.ecommerce-products.partials.sidebar'))
                </div>
                <div class="col-xl-8 col-lg-7">
                    @endif

                    @include(Theme::getThemeNamespace('views.ecommerce.includes.product-items'), ['itemsPerRow' => $itemsPerRow])

                    @if($shortcode->with_sidebar)
                </div>
            </div>
        @endif
    </div>
</section>
