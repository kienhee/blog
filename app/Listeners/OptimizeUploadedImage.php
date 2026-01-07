<?php

namespace App\Listeners;

use App\Services\ImageOptimizer;
use Illuminate\Support\Facades\Log;
use UniSharp\LaravelFilemanager\Events\ImageWasUploaded;

/**
 * Listener để tự động optimize và convert images sau khi upload
 */
class OptimizeUploadedImage
{
    protected ImageOptimizer $imageOptimizer;

    public function __construct(ImageOptimizer $imageOptimizer)
    {
        $this->imageOptimizer = $imageOptimizer;
    }

    /**
     * Handle the event.
     *
     * @param ImageWasUploaded $event
     * @return void
     */
    public function handle(ImageWasUploaded $event): void
    {
        $path = $event->path();
        
        // Lấy settings từ session
        $autoOptimize = session('lfm_auto_optimize', false);
        $autoConvert = session('lfm_auto_convert', false);
        $formats = session('lfm_convert_formats', []);

        // Nếu không có settings, không làm gì
        if (!$autoOptimize && !$autoConvert) {
            return;
        }

        try {
            // Lấy thông tin file một lần duy nhất (tối ưu hiệu suất)
            $pathInfo = pathinfo($path);
            $extension = strtolower($pathInfo['extension'] ?? '');
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];
            
            // Chỉ xử lý nếu là file ảnh
            if (!in_array($extension, $imageExtensions)) {
                return;
            }

            // Xử lý optimize và convert
            $options = [
                'optimize' => $autoOptimize,
                'convert' => $autoConvert,
                'formats' => is_array($formats) ? $formats : [],
            ];

            $result = $this->imageOptimizer->process($path, $options);

            // Sử dụng lại pathInfo đã lấy ở trên
            $filename = $pathInfo['basename'];
            $extension = strtoupper($extension);
            
            // Tạo dữ liệu chi tiết để hiển thị
            $optimizationData = [
                'filename' => $filename,
                'format' => $extension,
                'original_size' => 0,
                'optimized_size' => 0,
                'space_saved' => 0,
                'percent_saved' => 0,
                'method' => null,
                'converted_files' => [],
                'smallest_format' => null,
            ];

            // Thông tin optimize
            if (!empty($result['optimize']) && $result['optimize']['success']) {
                $optimizationData['original_size'] = $result['optimize']['original_size'] ?? 0;
                $optimizationData['optimized_size'] = $result['optimize']['optimized_size'] ?? 0;
                $optimizationData['space_saved'] = $result['optimize']['space_saved'] ?? 0;
                $optimizationData['method'] = $result['optimize']['method'] ?? 'unknown';
                $optimizationData['warning'] = $result['optimize']['warning'] ?? null;
                $optimizationData['note'] = $result['optimize']['note'] ?? null;
                
                // Tính phần trăm giảm
                if ($optimizationData['original_size'] > 0) {
                    $optimizationData['percent_saved'] = round(($optimizationData['space_saved'] / $optimizationData['original_size']) * 100, 1);
                }
            } elseif (!empty($result['optimize']) && !$result['optimize']['success']) {
                $optimizationData['error'] = $result['optimize']['message'] ?? 'Lỗi không xác định';
            }

            // Thông tin convert
            if (!empty($result['convert']) && $result['convert']['success'] && !empty($result['convert']['files'])) {
                $optimizationData['converted_files'] = $result['convert']['files'];
                $optimizationData['smallest_format'] = $result['smallest_format'];
            }

            // Lưu dữ liệu chi tiết vào session
            $existingData = session('lfm_optimization_data', []);
            $existingData[] = $optimizationData;
            session(['lfm_optimization_data' => $existingData]);

            // Log kết quả
            Log::info('Image optimization completed', [
                'path' => $path,
                'result' => $result,
                'optimization_data' => $optimizationData,
            ]);

            // Xóa settings khỏi session sau khi xử lý
            session()->forget(['lfm_auto_optimize', 'lfm_auto_convert', 'lfm_convert_formats']);
        } catch (\Throwable $e) {
            Log::error('Image optimization failed', [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

