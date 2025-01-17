@php
    $iconImage = $category->icon_image;
    $icon = $category->icon;
@endphp

@if ($iconImage || $icon)
    <span>
        @if ($iconImage)
            {{ RvMedia::image($iconImage, $category->name, attributes: ['loading' => false, 'style' => 'width: 18px; height: 18px']) }}
        @elseif ($icon)
            {!! BaseHelper::renderIcon($icon) !!}
        @endif
    </span>
@endif

{{ $category->name }}
