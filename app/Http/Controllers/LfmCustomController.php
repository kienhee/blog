<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use UniSharp\LaravelFilemanager\Controllers\UploadController as BaseUploadController;

/**
 * Custom Upload Controller để lưu settings optimize vào session
 */
class LfmCustomController extends BaseUploadController
{
    /**
     * Override upload method để lưu settings vào session trước khi upload
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload()
    {
        // Lấy settings từ request
        $autoOptimize = request()->has('auto_optimize') && request()->input('auto_optimize') == 'on';
        $autoConvert = request()->has('auto_convert') && request()->input('auto_convert') == 'on';
        $formats = request()->input('formats', []);

        // Lưu vào session để Listener có thể sử dụng
        session([
            'lfm_auto_optimize' => $autoOptimize,
            'lfm_auto_convert' => $autoConvert,
            'lfm_convert_formats' => is_array($formats) ? $formats : [],
        ]);

        // Gọi parent upload method
        return parent::upload();
    }
}

