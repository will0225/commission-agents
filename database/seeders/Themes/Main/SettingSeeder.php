<?php

namespace Database\Seeders\Themes\Main;

use Botble\Blog\Database\Traits\HasBlogSeeder;
use Botble\Ecommerce\Database\Seeders\Traits\HasEcommerceSettingsSeeder;
use Botble\Setting\Facades\Setting;
use Botble\Theme\Database\Seeders\ThemeSeeder;

class SettingSeeder extends ThemeSeeder
{
    use HasEcommerceSettingsSeeder;
    use HasBlogSeeder;

    public function run(): void
    {
        $this->uploadFiles('general', 'main');

        $settings = [
            'admin_favicon' => $this->filePath('general/favicon.png'),
            'admin_logo' => $this->filePath('general/logo-white.png'),
            'announcement_lazy_loading' => true,
            'marketplace_requires_vendor_documentations_verification' => 0,
        ];

        Setting::delete(array_keys($settings));

        Setting::set($settings)->save();

        $this->saveEcommerceSettings();

        $this->setPostSlugPrefix();
    }
}
