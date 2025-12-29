@php
    $breadcrumbs = $breadcrumbs ?? [];
    
    if (!empty($breadcrumbs)) {
        $breadcrumbList = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $breadcrumbList['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url']
            ];
        }
    }
@endphp

@if (!empty($breadcrumbs))
    <script type="application/ld+json">
        {!! json_encode($breadcrumbList, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endif