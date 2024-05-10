<?php

if (! function_exists('setting')) {
    /**
     * Get setting
     *
     * @param  string  $key
     * @param  string  $field
     * @return \App\Models\Setting | string
     */
    function setting($key, $field = null)
    {
        // Set / Store Cache
        $setting = \Cache::rememberForever("setting.$key", function () use ($key) {
            return \Facades\App\Models\Setting::where('key', $key)->first();
        });

        if (! $field) {
            return $setting;
        }

        return optional($setting)->{$field};
    }
}

if (! function_exists('updateSetting')) {
    /**
     * Update setting
     *
     * @param  string  $key
     * @param  array  $data
     * @param  string  $field
     * @return \App\Models\Setting | string
     */
    function updateSetting($key, $data, $field = null)
    {
        $setting = \Facades\App\Models\Setting::where('key', $key)->first();
        if (! $setting) {
            return null;
        }
        $setting->update($data);

        // delete exists cache
        if (\Cache::has("setting.$key")) {
            \Cache::pull("setting.$key");
        }

        return setting($key, $field);
    }
}

if (! function_exists('getMaxImageSize')) {
    /**
     * Get Max Image Size
     *
     * @return int
     */
    function getMaxImageSize()
    {
        return 5 * 1024;
    }
}

if (! function_exists('getImageTypesValidation')) {
    /**
     * Get Image Types Validation
     *
     * @return string
     */
    function getImageTypesValidation()
    {
        return 'jpg,jpeg,bmp,png,gif,svg';
    }
}
