<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Tinify\Tinify;
use Tinify\Source;
use Exception;

/**
 * Service để optimize và convert images
 * 
 * Hỗ trợ:
 * - Optimize bằng TinyPNG API
 * - Convert sang nhiều format: WebP, AVIF, JPEG, PNG
 * - So sánh và tìm format có dung lượng nhỏ nhất
 */
class ImageOptimizer
{
    protected ?Tinify $tinify = null;
    protected ImageManager $imageManager;
    protected bool $hasTinyPNGKey;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
        
        // Cache API key để tránh gọi config nhiều lần
        $apiKey = config('services.tinypng.key');
        $this->hasTinyPNGKey = !empty($apiKey);
        
        if ($this->hasTinyPNGKey) {
            try {
                Tinify::setKey($apiKey);
                $this->tinify = new Tinify();
            } catch (Exception $e) {
                Log::warning('TinyPNG initialization failed: ' . $e->getMessage());
                $this->hasTinyPNGKey = false;
            }
        }
    }

    /**
     * Optimize ảnh bằng TinyPNG API
     * 
     * @param string $path Đường dẫn file ảnh (absolute hoặc relative to storage)
     * @return array Kết quả optimize: ['success' => bool, 'original_size' => int, 'optimized_size' => int, 'space_saved' => int]
     */
    public function optimizeWithTinyPNG(string $path): array
    {
        // Nếu không có TinyPNG API key, trả về lỗi
        if (!$this->hasTinyPNGKey) {
            return [
                'success' => false,
                'message' => 'TinyPNG API key chưa được cấu hình. Vui lòng thêm TINIFY_API_KEY vào file .env',
            ];
        }

        try {
            // Lấy absolute path
            $absolutePath = $this->getAbsolutePath($path);
            
            if (!file_exists($absolutePath)) {
                return [
                    'success' => false,
                    'message' => 'File không tồn tại: ' . $path,
                ];
            }

            // Đọc file size TRƯỚC khi optimize (đảm bảo đọc đúng)
            clearstatcache(true, $absolutePath);
            $originalSize = filesize($absolutePath);
            
            if ($originalSize <= 0) {
                return [
                    'success' => false,
                    'message' => 'Không thể đọc kích thước file: ' . $path,
                ];
            }

            // Optimize với TinyPNG
            $source = Source::fromFile($absolutePath);
            $optimizedData = $source->toBuffer();

            // Lưu file đã optimize
            file_put_contents($absolutePath, $optimizedData);
            
            // Clear cache và đọc lại file size SAU khi optimize
            clearstatcache(true, $absolutePath);
            $optimizedSize = filesize($absolutePath);
            $spaceSaved = $originalSize - $optimizedSize;

            // Nếu không giảm được dung lượng, có thể ảnh đã được optimize hoặc không thể optimize thêm
            if ($spaceSaved == 0 && $originalSize > 0) {
                Log::info('TinyPNG optimization completed but no size reduction', [
                    'path' => $path,
                    'original_size' => $originalSize,
                    'optimized_size' => $optimizedSize,
                    'note' => 'Image may already be optimized or cannot be optimized further. This can happen if: 1) Image was already optimized, 2) Image format does not support further compression, 3) Image is too simple/small',
                ]);
            } else {
                Log::info('TinyPNG optimization successful', [
                    'path' => $path,
                    'original_size' => $originalSize,
                    'optimized_size' => $optimizedSize,
                    'space_saved' => $spaceSaved,
                ]);
            }

            return [
                'success' => true,
                'original_size' => $originalSize,
                'optimized_size' => $optimizedSize,
                'space_saved' => $spaceSaved,
                'method' => 'tinypng',
                'note' => $spaceSaved == 0 && $originalSize > 0 ? 'Ảnh có thể đã được tối ưu hóa trước đó hoặc không thể tối ưu thêm. Một số ảnh đã được nén tối đa hoặc có định dạng không hỗ trợ tối ưu thêm.' : null,
            ];
        } catch (Exception $e) {
            Log::error('TinyPNG optimization failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'TinyPNG optimization failed: ' . $e->getMessage(),
                'method' => 'tinypng',
            ];
        }
    }


    /**
     * Convert ảnh sang nhiều format
     * 
     * @param string $sourcePath Đường dẫn file ảnh gốc
     * @param array $formats Các format muốn convert: ['webp', 'avif', 'jpeg', 'png']
     * @return array Kết quả convert: ['success' => bool, 'files' => array]
     */
    public function convertToFormats(string $sourcePath, array $formats): array
    {
        try {
            $absolutePath = $this->getAbsolutePath($sourcePath);
            
            if (!file_exists($absolutePath)) {
                return [
                    'success' => false,
                    'message' => 'File không tồn tại: ' . $sourcePath,
                ];
            }

            $pathInfo = pathinfo($absolutePath);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $convertedFiles = [];

            foreach ($formats as $format) {
                try {
                    $outputPath = $directory . '/' . $filename . '.' . $format;
                    
                    // Load ảnh gốc cho mỗi format (cần thiết vì mỗi format cần xử lý riêng)
                    $convertedImage = $this->imageManager->read($absolutePath);
                    
                    // Convert và lưu
                    match ($format) {
                        'webp' => $convertedImage->toWebp(90)->save($outputPath),
                        'avif' => $convertedImage->toAvif(90)->save($outputPath),
                        'jpeg', 'jpg' => $convertedImage->toJpeg(90)->save($outputPath),
                        'png' => $convertedImage->toPng()->save($outputPath),
                        default => null,
                    };

                    if (file_exists($outputPath)) {
                        // Clear cache để đảm bảo đọc đúng file size
                        clearstatcache(true, $outputPath);
                        $convertedFiles[] = [
                            'format' => $format,
                            'path' => $this->getRelativePath($outputPath),
                            'size' => filesize($outputPath),
                        ];
                    }
                } catch (Exception $e) {
                    Log::warning("Failed to convert to {$format}", [
                        'source' => $sourcePath,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Tiếp tục với format khác
                }
            }

            return [
                'success' => true,
                'files' => $convertedFiles,
            ];
        } catch (Exception $e) {
            Log::error('Convert formats failed', [
                'source' => $sourcePath,
                'formats' => $formats,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Convert failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Tìm format có dung lượng nhỏ nhất
     * 
     * @param array $files Mảng các file đã convert: [['format' => 'webp', 'path' => '...', 'size' => 1234], ...]
     * @return array|null File có dung lượng nhỏ nhất hoặc null
     */
    public function getSmallestFormat(array $files): ?array
    {
        if (empty($files)) {
            return null;
        }

        $smallest = $files[0];
        
        foreach ($files as $file) {
            if ($file['size'] < $smallest['size']) {
                $smallest = $file;
            }
        }

        return $smallest;
    }

    /**
     * Xử lý tổng hợp: optimize + convert
     * 
     * @param string $path Đường dẫn file ảnh
     * @param array $options Options: ['optimize' => bool, 'convert' => bool, 'formats' => array]
     * @return array Kết quả xử lý
     */
    public function process(string $path, array $options = []): array
    {
        $result = [
            'path' => $path,
            'optimize' => null,
            'convert' => null,
            'smallest_format' => null,
        ];

        // Optimize nếu được yêu cầu
        if (!empty($options['optimize'])) {
            $optimizeResult = $this->optimizeWithTinyPNG($path);
            $result['optimize'] = $optimizeResult;
        }

        // Convert nếu được yêu cầu
        if (!empty($options['convert']) && !empty($options['formats'])) {
            $convertResult = $this->convertToFormats($path, $options['formats']);
            $result['convert'] = $convertResult;

            // Tìm format nhỏ nhất
            if ($convertResult['success'] && !empty($convertResult['files'])) {
                $smallest = $this->getSmallestFormat($convertResult['files']);
                $result['smallest_format'] = $smallest;
            }
        }

        return $result;
    }

    /**
     * Lấy absolute path từ relative path
     * 
     * @param string $path Đường dẫn (relative hoặc absolute)
     * @return string Absolute path
     */
    protected function getAbsolutePath(string $path): string
    {
        // Nếu đã là absolute path
        if (str_starts_with($path, '/') || str_starts_with($path, storage_path())) {
            return $path;
        }

        // Nếu là storage path (storage/app/public/...)
        if (str_starts_with($path, 'storage/')) {
            return storage_path('app/public/' . str_replace('storage/', '', $path));
        }

        // Nếu là public path (public/...)
        if (str_starts_with($path, 'public/')) {
            return public_path(str_replace('public/', '', $path));
        }

        // Mặc định là storage/app/public
        return storage_path('app/public/' . $path);
    }

    /**
     * Lấy relative path từ absolute path
     * 
     * @param string $absolutePath Absolute path
     * @return string Relative path (relative to storage/app/public)
     */
    protected function getRelativePath(string $absolutePath): string
    {
        $storagePath = storage_path('app/public/');
        
        if (str_starts_with($absolutePath, $storagePath)) {
            return str_replace($storagePath, '', $absolutePath);
        }

        return $absolutePath;
    }
}

