@php
    $organizationSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => config('app.name'),
        'url' => route('client.home', [], true),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => asset_shared_url('images/favicon.png')
        ],
        'description' => config('seo.description.fallback', 'Blog chia sẻ kiến thức về lập trình, công nghệ và cuộc sống'),
        'inLanguage' => 'vi-VN'
    ];
    
    // Add social media links if available (can be extended)
    $sameAs = [];
    // Example: Add social media URLs here if you have them
    // $sameAs[] = 'https://facebook.com/yourpage';
    // $sameAs[] = 'https://twitter.com/yourhandle';
    
    if (!empty($sameAs)) {
        $organizationSchema['sameAs'] = $sameAs;
    }
@endphp

<script type="application/ld+json">
    {!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>

