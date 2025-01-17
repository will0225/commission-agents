<?php

use Botble\Ads\Facades\AdsManager;
use Botble\Ads\Models\Ads;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\FieldOptions\ColorFieldOption;
use Botble\Base\Forms\FieldOptions\InputFieldOption;
use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\FieldOptions\UiSelectorFieldOption;
use Botble\Base\Forms\Fields\CheckboxField;
use Botble\Base\Forms\Fields\ColorField;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\Fields\MultiCheckListField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\Fields\UiSelectorField;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Models\BaseQueryBuilder;
use Botble\Ecommerce\Enums\DiscountTypeEnum;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Brand;
use Botble\Ecommerce\Models\Discount;
use Botble\Ecommerce\Models\FlashSale;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Models\ProductCollection;
use Botble\Ecommerce\Repositories\Interfaces\ProductInterface;
use Botble\Shortcode\Compilers\Shortcode as ShortcodeCompiler;
use Botble\Shortcode\Facades\Shortcode;
use Botble\Shortcode\Forms\Fields\ShortcodeColorField;
use Botble\Shortcode\Forms\Fields\ShortcodeTagsField;
use Botble\Shortcode\Forms\ShortcodeForm;
use Botble\Shortcode\ShortcodeField;
use Botble\Theme\Facades\Theme;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

app()->booted(function (): void {
    if (! is_plugin_active('ecommerce')) {
        return;
    }

    Shortcode::register(
        'ecommerce-categories',
        __('Ecommerce Categories'),
        __('Ecommerce Categories'),
        function (ShortcodeCompiler $shortcode) {
            $categoryIds = Shortcode::fields()->getIds('category_ids', $shortcode);

            if (! $categoryIds) {
                return null;
            }

            $categories = ProductCategory::query()
                ->whereIn('id', $categoryIds)
                ->wherePublished()
                ->when($shortcode->show_products_count !== 'no', function ($query): void {
                    $query->withCount('products');
                })
                ->with('slugable')
                ->orderBy('order')
                ->get();

            if ($categories->isEmpty()) {
                return null;
            }

            $shortcode->background_color = $shortcode->background_color ?: '#F3F5F7';

            $shortcode->show_products_count = $shortcode->show_products_count !== 'no';

            return Theme::partial('shortcodes.ecommerce-categories.index', compact('shortcode', 'categories'));
        }
    );

    Shortcode::setPreviewImage('product-categories', Theme::asset()->url('images/shortcodes/ecommerce-categories/slider.png'));

    Shortcode::setAdminConfig('ecommerce-categories', function (array $attributes) {
        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'style',
                UiSelectorField::class,
                SelectFieldOption::make()
                    ->choices([
                        'grid' => [
                            'label' => __('Grid'),
                            'image' => Theme::asset()->url('images/shortcodes/ecommerce-categories/grid.png'),
                        ],
                        'slider' => [
                            'label' => __('Slider'),
                            'image' => Theme::asset()->url('images/shortcodes/ecommerce-categories/slider.png'),
                        ],
                        'list' => [
                            'label' => __('List'),
                            'image' => Theme::asset()->url('images/shortcodes/ecommerce-categories/slider.png'),
                        ],
                    ])
                    ->selected(Arr::get($attributes, 'style', 'grid'))
            )
            ->add(
                'category_ids',
                SelectField::class,
                SelectFieldOption::make()
                    ->choices(
                        ProductCategory::query()
                            ->wherePublished()
                            ->pluck('name', 'id')
                            ->all()
                    )
                    ->label(__('Choose categories'))
                    ->selected(ShortcodeField::parseIds(Arr::get($attributes, 'category_ids')))
                    ->searchable()
                    ->multiple()
            )
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
            )
            ->add(
                'subtitle',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Subtitle'))
            )
            ->add(
                'background_color',
                ShortcodeColorField::class,
                InputFieldOption::make()
                    ->defaultValue('#F3F5F7')
                    ->label(__('Background color'))
            )
            ->add(
                'items_per_view',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(__('Items per view'))
                    ->collapsible('style', 'slider', Arr::get($attributes, 'style') === 'slider')
                    ->defaultValue(5)
            )
            ->add(
                'show_products_count',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Show products count?'))
                    ->choices(['no' => __('No'), 'yes' => __('Yes')])
                    ->defaultValue('yes')
            );
    });

    Shortcode::register('ecommerce-brands', __('Ecommerce Brands'), __('Ecommerce Brands'), function (ShortcodeCompiler $shortcode) {
        $brandIds = Shortcode::fields()->getIds('brand_ids', $shortcode);

        $brands = Brand::query()
            ->wherePublished()
            ->when($brandIds, fn ($query) => $query->whereIn('id', $brandIds))
            ->orderBy('order')
            ->get();

        if ($brands->isEmpty()) {
            return null;
        }

        return Theme::partial('shortcodes.ecommerce-brands.index', compact('shortcode', 'brands'));
    });

    Shortcode::setAdminConfig('ecommerce-brands', function (array $attributes) {
        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
            )
            ->add(
                'subtitle',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Subtitle'))
            )
            ->add(
                'brand_ids',
                SelectField::class,
                SelectFieldOption::make()
                    ->choices(
                        Brand::query()
                            ->wherePublished()
                            ->pluck('name', 'id')
                            ->all()
                    )
                    ->label(__('Choose brands'))
                    ->selected(ShortcodeField::parseIds(Arr::get($attributes, 'brand_ids')))
                    ->helperText(__('Leave empty to show all brands'))
                    ->searchable()
                    ->multiple()
            )
            ->add(
                'show_brand_name',
                CheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(__('Show brand name'))
                    ->defaultValue(false)
            );
    });

    Shortcode::register('ecommerce-flash-sale', __('Ecommerce Flash Sale'), __('Ecommerce Flash Sale'), function (ShortcodeCompiler $shortcode) {
        $limit = (int) $shortcode->limit ?: 5;

        // @phpstan-ignore-next-line
        $flashSale = FlashSale::query()
            ->notExpired()
            ->where('id', $shortcode->flash_sale_id)
            ->wherePublished()
            ->with([
                'products' => function (BelongsToMany|BaseQueryBuilder $query) use ($limit) {
                    $reviewParams = EcommerceHelper::withReviewsParams();

                    if (EcommerceHelper::isReviewEnabled()) {
                        $query->withAvg($reviewParams['withAvg'][0], $reviewParams['withAvg'][1]);
                    }

                    return $query
                        ->wherePublished()
                        ->with(EcommerceHelper::withProductEagerLoadingRelations())
                        ->take($limit)
                        ->withCount($reviewParams['withCount']);
                },
            ])
            ->first();

        if (! $flashSale || $flashSale->products->isEmpty()) {
            return null;
        }

        return Theme::partial('shortcodes.ecommerce-flash-sale.index', compact('shortcode', 'flashSale'));
    });

    Shortcode::setPreviewImage('ecommerce-flash-sale', Theme::asset()->url('images/shortcodes/ecommerce-flash-sale/style-1.png'));

    Shortcode::setAdminConfig('ecommerce-flash-sale', function (array $attributes) {
        // @phpstan-ignore-next-line
        $flashSales = FlashSale::query()
            ->wherePublished()
            ->notExpired()
            ->pluck('name', 'id')
            ->all();

        $styles = [];

        foreach (range(1, 2) as $i) {
            $styles[$i] = [
                'label' => __('Style :number', ['number' => $i]),
                'image' => Theme::asset()->url("images/shortcodes/ecommerce-flash-sale/style-$i.png"),
            ];
        }

        $isStyle = fn (int $style) => Arr::get($attributes, 'style', 1) == $style;

        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->columns()
            ->add(
                'style',
                UiSelectorField::class,
                UiSelectorFieldOption::make()
                    ->colspan(2)
                    ->choices($styles)
                    ->selected(Arr::get($attributes, 'style', 1))
                    ->collapsible('style')
            )
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
            )
            ->add(
                'subtitle',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Subtitle'))
                    ->colspan(2)
                    ->collapseTrigger('style', 2, $isStyle(2))
            )
            ->add(
                'flash_sale_id',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Select a flash sale'))
                    ->choices($flashSales)
                    ->colspan(2)
            )
            ->add(
                'limit',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(__('Limit'))
                    ->placeholder(__('Number of deal products to show'))
                    ->defaultValue(3)
                    ->colspan(2)
                    ->collapseTrigger('style', 1, $isStyle(1))
            )
            ->add(
                'button_label',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Button label'))
                    ->placeholder(__('Button view more label'))
                    ->collapseTrigger('style', 1, $isStyle(1))
            )
            ->add(
                'button_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Button URL'))
                    ->placeholder(__('Button view more URL'))
                    ->helperText(__('Leave empty to link to the shop page'))
                    ->collapseTrigger('style', 1, $isStyle(1))
            )
            ->add(
                'background_color',
                ColorField::class,
                InputFieldOption::make()
                    ->label(__('Background color'))
                    ->defaultValue('#F3F3F3')
            )
            ->add(
                'background_image',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Background image'))
            );
    });

    Shortcode::register(
        'ecommerce-products',
        __('Ecommerce Products'),
        __('Ecommerce Products'),
        function (ShortcodeCompiler $shortcode) {
            $condition = [
                'ec_products.is_variation' => 0,
            ];

            if ($productIds = Shortcode::fields()->getIds('product_ids', $shortcode)) {
                $condition[] = ['ec_products.id', 'IN', $productIds];
            }

            $products = app(ProductInterface::class)->filterProducts([
                'categories' => $categoryIds = Shortcode::fields()->getIds('category_ids', $shortcode),
                'collections' => Shortcode::fields()->getIds('collection_ids', $shortcode),
            ], [
                'take' => (int) $shortcode->limit ?: 12,
                'order_by' => [
                    'order' => 'ASC',
                    'created_at' => 'DESC',
                ],
                'condition' => $condition,
                ...EcommerceHelper::withReviewsParams(),
            ]);

            $products = $products instanceof Product ? collect([$products]) : $products;

            if ($products->isEmpty()) {
                return null;
            }

            $ads = [];

            if (
                is_plugin_active('ads')
                && ! empty($adsIds = Shortcode::fields()->getIds('ads_ids', $shortcode))
            ) {
                $ads = Ads::query()
                    ->whereIn('id', $adsIds)
                    ->wherePublished()
                    ->get();
            }

            $categories = collect();

            if ($categoryIds) {
                $categories = ProductCategory::query()
                    ->wherePublished()
                    ->whereIn('id', $categoryIds)
                    ->get();
            }

            return Theme::partial(
                'shortcodes.ecommerce-products.index',
                compact('shortcode', 'products', 'ads', 'categoryIds', 'categories')
            );
        }
    );

    Shortcode::setAdminConfig('ecommerce-products', function (array $attributes) {
        $withSidebar = in_array(Arr::get($attributes, 'with_sidebar', false), ['1', 'on']);

        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'style',
                UiSelectorField::class,
                SelectFieldOption::make()
                    ->choices([
                        'grid' => [
                            'label' => __('Grid'),
                            'image' => Theme::asset()->url('images/shortcodes/ecommerce-products/grid.png'),
                        ],
                        'slider' => [
                            'label' => __('Slider'),
                            'image' => Theme::asset()->url('images/shortcodes/ecommerce-products/slider.png'),
                        ],
                        'simple' => [
                            'label' => __('Simple'),
                            'image' => Theme::asset()->url('images/shortcodes/ecommerce-products/simple.png'),
                        ],
                        'slider-full-width' => [
                            'label' => __('Slider full width'),
                            'image' => Theme::asset()->url('images/shortcodes/ecommerce-products/slider-full-width.png'),
                        ],
                    ])
                    ->selected(Arr::get($attributes, 'style', 'grid'))
                    ->collapsible('style')
            )
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
            )
            ->add(
                'subtitle',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Subtitle'))
            )
            ->add(
                'category_ids',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Categories'))
                    ->multiple()
                    ->ajaxSearch()
                    ->ajaxUrl(route('admin.ajax.search-categories'))
                    ->selected(
                        ProductCategory::query()
                            ->whereIn('id', ShortcodeField::parseIds(Arr::get($attributes, 'category_ids')))
                            ->pluck('name', 'id')
                            ->all()
                    )
            )
            ->add(
                'collection_ids',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Collections'))
                    ->multiple()
                    ->ajaxSearch()
                    ->ajaxUrl(route('admin.ajax.search-collections'))
                    ->selected(
                        ProductCollection::query()
                            ->whereIn('id', ShortcodeField::parseIds(Arr::get($attributes, 'collection_ids')))
                            ->pluck('name', 'id')
                            ->all()
                    )
            )
            ->add(
                'product_ids',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Specify products'))
                    ->multiple()
                    ->ajaxSearch()
                    ->ajaxUrl(route('admin.ajax.search-products'))
                    ->selected(
                        Product::query()
                            ->whereIn('id', ShortcodeField::parseIds(Arr::get($attributes, 'product_ids')))
                            ->pluck('name', 'id')
                            ->all()
                    )
            )
            ->add(
                'limit',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(__('Number of products to show'))
                    ->defaultValue(12)
            )
            ->add(
                'slides_to_show',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(__('Slides to show (if style is slider)'))
                    ->defaultValue(4)
            )
            ->add(
                'items_per_row',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(__('Items per row (if style is grid)'))
                    ->defaultValue(4)
            )
            ->add(
                'border_color',
                ColorField::class,
                ColorFieldOption::make()
                    ->label(__('Border color'))
                    ->defaultValue('#fd4b6b')
            )
            ->add(
                'background_color',
                ColorField::class,
                ColorFieldOption::make()
                    ->label(__('Background color'))
                    ->defaultValue('#EFF1F5')
                    ->collapseTrigger('style', 'slider-full-width', Arr::get($attributes, 'style') === 'slider-full-width')
            )
            ->add(
                'with_sidebar',
                CheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(__('Has sidebar'))
                    ->defaultValue(false)
            )
            ->addOpenCollapsible('with_sidebar', '1', $withSidebar == '1')
            ->add(
                'image',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Image'))
                    ->helperText(__('Leave empty to use the category image'))
                    ->colspan(2)
            )
            ->add(
                'action_label',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Action Label'))
            )
            ->add(
                'action_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Action URL'))
                    ->helperText(__('Leave empty to link to the category page'))
            )
            ->when(is_plugin_active('ads'), function (FormAbstract $form) use ($attributes): void {
                $form->add(
                    'ads_ids',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Ads'))
                        ->choices(AdsManager::getData(true)->pluck('name', 'id')->all())
                        ->selected(ShortcodeField::parseIds(Arr::get($attributes, 'ads_ids')))
                        ->multiple()
                        ->searchable()
                        ->colspan(2)
                );
            })
            ->addCloseCollapsible('with_sidebar', '1');
    });

    Shortcode::register(
        'ecommerce-product-groups',
        __('Ecommerce Product Groups'),
        __('Ecommerce Product Groups'),
        function (ShortcodeCompiler $shortcode) {
            $productTabs = [
                'all' => __('All'),
                'featured' => __('Featured'),
                'on-sale' => __('On sale'),
                'trending' => __('Trending'),
                'top-rated' => __('Top rated'),
            ];

            $selectedTabs = Shortcode::fields()->parseIds($shortcode->tabs);

            if (empty($selectedTabs)) {
                $selectedTabs = array_keys($productTabs);
            }

            $style = in_array($shortcode->style, ['tabs', 'columns']) ? $shortcode->style : 'tabs';

            $groups = [];

            if ($style === 'columns') {
                $limit = (int) $shortcode->limit ?: 15;

                $params = ['take' => $limit];

                foreach ($selectedTabs as $tab) {
                    $groups[$tab] = match ($tab) {
                        'featured' => [
                            'title' => __('Featured'),
                            'products' => get_featured_products($params),
                        ],
                        'on-sale' => [
                            'title' => __('On Sale'),
                            'products' => get_products_on_sale($params),
                        ],
                        'trending' => [
                            'title' => __('Trending Products'),
                            'products' => get_trending_products($params),
                        ],

                        'top-rated' => [
                            'title' => __('Top Rated'),
                            'products' => get_top_rated_products($limit),
                        ],
                        default => [
                            'title' => __('All Products'),
                            'products' => get_products($params + EcommerceHelper::withReviewsParams()),
                        ],
                    };

                    if (! $groups[$tab]['products'] instanceof Collection) {
                        $groups[$tab]['products'] = collect($groups[$tab]['products'] instanceof Product ? [$groups[$tab]['products']] : $groups[$tab]['products']);
                    }

                    if ($groups[$tab]['products']->isEmpty()) {
                        unset($groups[$tab]);
                    }
                }
            }

            return Theme::partial(
                'shortcodes.ecommerce-product-groups.index',
                compact('shortcode', 'productTabs', 'selectedTabs', 'groups', 'style')
            );
        }
    );

    Shortcode::setPreviewImage('ecommerce-product-groups', Theme::asset()->url('images/shortcodes/ecommerce-product-groups/tabs.png'));

    Shortcode::setAdminConfig('ecommerce-product-groups', function (array $attributes) {
        $productTabs = [
            'all' => __('All'),
            'featured' => __('Featured'),
            'on-sale' => __('On sale'),
            'trending' => __('Trending'),
            'top-rated' => __('Top rated'),
        ];

        $selectedTabs = Shortcode::fields()->parseIds(Arr::get($attributes, 'tabs'));

        if (empty($selectedTabs)) {
            $selectedTabs = array_keys($productTabs);
        }

        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'style',
                UiSelectorField::class,
                SelectFieldOption::make()
                    ->choices([
                        'tabs' => [
                            'label' => __('Tabs'),
                            'image' => Theme::asset()->url('images/shortcodes/ecommerce-product-groups/tabs.png'),
                        ],
                        'columns' => [
                            'label' => __('Columns'),
                            'image' => Theme::asset()->url('images/shortcodes/ecommerce-product-groups/columns.png'),
                        ],
                    ])
                    ->selected(Arr::get($attributes, 'style', 'tabs'))
                    ->collapsible('style')
            )
            ->add(
                'title',
                TextField::class,
            )
            ->add(
                'subtitle',
                TextField::class,
            )
            ->add(
                'limit',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(__('Limit'))
                    ->placeholder(__('Number of products to show'))
                    ->defaultValue(8)
            )
            ->add(
                'tabs[]',
                MultiCheckListField::class,
                [
                    'label' => __('Groups'),
                    'choices' => $productTabs,
                    'value' => $selectedTabs,
                ]
            );
    });

    Shortcode::register(
        'ecommerce-coupons',
        __('Ecommerce Coupons'),
        __('Ecommerce Coupons'),
        function (ShortcodeCompiler $shortcode) {
            $couponIds = Shortcode::fields()->parseIds($shortcode->coupon_ids);

            if (empty($couponIds)) {
                return null;
            }

            // @phpstan-ignore-next-line
            $coupons = Discount::query()
                ->whereIn('id', $couponIds)
                ->where('type', DiscountTypeEnum::COUPON)
                ->active()
                ->available()
                ->get();

            if ($coupons->isEmpty()) {
                return null;
            }

            return Theme::partial('shortcodes.ecommerce-coupons.index', compact('shortcode', 'coupons'));
        }
    );

    Shortcode::setPreviewImage('ecommerce-coupons', Theme::asset()->url('images/shortcodes/ecommerce-coupons.png'));

    Shortcode::setAdminConfig('ecommerce-coupons', function (array $attributes) {
        // @phpstan-ignore-next-line
        $coupons = Discount::query()
            ->where('type', DiscountTypeEnum::COUPON)
            ->active()
            ->available()
            ->get()
            ->mapWithKeys(function (Discount $discount) {
                return [
                    $discount->getKey() => sprintf(
                        '%s - %s',
                        $discount->code,
                        get_discount_description($discount)
                    ),
                ];
            });

        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'coupon_ids',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Coupons'))
                    ->choices($coupons->all())
                    ->multiple()
                    ->searchable()
            );
    });

    Shortcode::register('ecommerce-collections', __('Ecommerce Collections'), __('Ecommerce Collections'), function (ShortcodeCompiler $shortcode) {
        $collectionIds = Shortcode::fields()->getIds('collection_ids', $shortcode);

        if (! $collectionIds) {
            return null;
        }

        $collections = ProductCollection::query()
            ->wherePublished()
            ->withCount('products')
            ->whereIn('id', $collectionIds)
            ->get();

        if ($collections->isEmpty()) {
            return null;
        }

        return Theme::partial('shortcodes.ecommerce-collections.index', compact('shortcode', 'collections'));
    });

    Shortcode::setPreviewImage('ecommerce-collections', Theme::asset()->url('images/shortcodes/ecommerce-categories/slider.png'));

    Shortcode::setAdminConfig('ecommerce-collections', function (array $attributes) {
        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add('collection_ids', ShortcodeTagsField::class, [
                'label' => __('Collections'),
                'attr' => [
                    'placeholder' => __('Choose collections'),
                ],
                'choices' => ProductCollection::query()
                    ->wherePublished()
                    ->pluck('name', 'id')
                    ->all(),
            ])
            ->add(
                'items_per_view',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(__('Items per view'))
                    ->defaultValue(5)
            );
    });
});
