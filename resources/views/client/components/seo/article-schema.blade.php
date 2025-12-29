@php
    $post = $post ?? null;
    
    if ($post && isset($postModel) && $postModel) {
        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->description ?? strip_tags(substr($post->content ?? '', 0, 200)),
            'datePublished' => $postModel->created_at->toIso8601String(),
            'dateModified' => $postModel->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $postModel->user->full_name ?? config('app.name')
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('favicon.ico')
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('client.post', ['slug' => $post->slug])
            ],
            'url' => route('client.post', ['slug' => $post->slug])
        ];
        
        // Add image if available
        if ($postModel->thumbnail) {
            $articleSchema['image'] = [
                '@type' => 'ImageObject',
                'url' => asset($postModel->thumbnail),
                'width' => 1200,
                'height' => 630
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
        {!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endif