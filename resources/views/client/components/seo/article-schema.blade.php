@php
    $post = $post ?? null;
    
    if ($post && isset($postModel) && $postModel) {
        // Prepare description - decode HTML entities and clean up
        $description = $post->description ?? strip_tags(substr($post->content ?? '', 0, 200));
        if ($description) {
            // Decode HTML entities to plain text for JSON-LD
            $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Clean up extra whitespace
            $description = preg_replace('/\s+/', ' ', $description);
            $description = trim($description);
        }
        
        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $description,
            'datePublished' => $postModel->created_at->toIso8601String(),
            'dateModified' => $postModel->updated_at->toIso8601String(),
            'inLanguage' => 'vi-VN',
            'author' => [
                '@type' => 'Person',
                'name' => $postModel->user->full_name ?? config('app.name')
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset_shared_url('images/favicon.png')
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('client.post', ['slug' => $post->slug], true)
            ],
            'url' => route('client.post', ['slug' => $post->slug], true)
        ];
        
        // Add image if available
        if ($postModel->thumbnail) {
            // Build absolute URL for image with proper encoding
            $imageUrl = $postModel->thumbnail;
            if (!str_starts_with($imageUrl, 'http')) {
                // Ensure path starts with /
                $path = '/' . ltrim($imageUrl, '/');
                // Encode each path segment properly (keep slashes)
                $pathSegments = explode('/', trim($path, '/'));
                $encodedSegments = array_map(function($segment) {
                    return $segment ? rawurlencode($segment) : '';
                }, $pathSegments);
                $encodedPath = '/' . implode('/', array_filter($encodedSegments));
                // Build full URL
                $baseUrl = rtrim(config('app.url'), '/');
                $imageUrl = $baseUrl . $encodedPath;
            }
            // Use actual image dimensions if available, otherwise use common blog image size
            $articleSchema['image'] = [
                '@type' => 'ImageObject',
                'url' => $imageUrl,
                'width' => 1200, // Standard OG image width
                'height' => 630  // Standard OG image height (1.91:1 ratio)
            ];
        }
        
        // Add article section if category exists
        if ($postModel->category) {
            $articleSchema['articleSection'] = $postModel->category->name;
        }
        
        // Add keywords if hashtags exist
        if (isset($hashtags) && !empty($hashtags)) {
            $articleSchema['keywords'] = implode(', ', array_column($hashtags, 'name'));
        }
    }
@endphp

@if ($post && isset($postModel) && $postModel)
    <script type="application/ld+json">
        {!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endif