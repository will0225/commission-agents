<?php

use Botble\Base\Facades\BaseHelper;
use Botble\SalePopup\Http\Controllers\SalePopupController;
use Botble\SalePopup\Http\Controllers\Settings\SalePopupSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'core'])->group(function (): void {
    Route::group(['prefix' => BaseHelper::getAdminPrefix(), 'middleware' => 'auth'], function (): void {
        Route::group(['prefix' => 'settings/sale-popup', 'as' => 'sale-popup.settings', 'permission' => 'sale-popup.settings'], function (): void {
            Route::get('/', [SalePopupSettingController::class, 'edit']);
            Route::put('/', [SalePopupSettingController::class, 'update'])->name('.edit');
        });
    });

    Route::group(apply_filters(BASE_FILTER_GROUP_PUBLIC_ROUTE, []), function (): void {
        Route::get('ajax/sale-popup/products', [SalePopupController::class, 'ajaxSalePopup'])
            ->name('public.ajax.sale-popup');
    });
});
