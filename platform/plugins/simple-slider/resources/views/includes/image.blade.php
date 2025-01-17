@php
    $slider->loadMissing('metadata');
    $tabletImage = $slider->getMetaData('tablet_image', true) ?: $slider->image;
    $mobileImage = $slider->getMetaData('mobile_image', true) ?: $tabletImage;
@endphp
<picture>
    <source srcset="{{ RvMedia::getImageUrl($slider->image, null, false, RvMedia::getDefaultImage()) }}" media="(min-width: 1200px)" />
    <source srcset="{{ RvMedia::getImageUrl($tabletImage, null, false, RvMedia::getDefaultImage()) }}" media="(min-width: 768px)" />
    <source srcset="{{ RvMedia::getImageUrl($mobileImage, null, false, RvMedia::getDefaultImage()) }}" media="(max-width: 767px)" />
    {{ RvMedia::image($slider->image, attributes: $attributes ?? []) }}
</picture>
