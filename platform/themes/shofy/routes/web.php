<?php

use Botble\Base\Http\Middleware\RequiresJsonRequestMiddleware;
use Botble\Ecommerce\Http\Controllers\Fronts\PublicAjaxController;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;
use Theme\Shofy\Http\Controllers\ShofyController;

if (is_plugin_active('ecommerce')) {
    Route::controller(ShofyController::class)
        ->middleware(RequiresJsonRequestMiddleware::class)
        ->group(function (): void {
            Theme::registerRoutes(function (): void {
                Route::prefix('ajax')->name('public.ajax.')->group(function (): void {
                    Route::get('products', 'ajaxGetProducts')->name('products');
                    Route::get('search-products', [PublicAjaxController::class, 'ajaxSearchProducts'])
                        ->name('search-products');
                    Route::get('categories-dropdown', [PublicAjaxController::class, 'ajaxGetCategoriesDropdown'])
                        ->name('categories-dropdown');
                    Route::get('cart-content', 'ajaxGetCartContent')
                        ->name('cart-content');
                    Route::get('cross-sale-products/{product}', 'ajaxGetCrossSaleProducts')
                        ->name('cross-sale-products');
                    Route::get('related-products/{product}', 'ajaxGetRelatedProducts')
                        ->name('related-products');
                });
            });
        });
}

Theme::routes();
