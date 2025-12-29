@php
    $keywords = $keywords ?? null;
    $post = $post ?? null;
    $category = $category ?? null;
    
    // Build keywords if not provided
    if (!$keywords) {
        $keywordArray = [];
        
        if ($post && isset($postModel) && $postModel) {
            // Add hashtags
            if ($postModel->hashtags && $postModel->hashtags->isNotEmpty()) {
                $keywordArray = array_merge($keywordArray, $postModel->hashtags->pluck('name')->toArray());
            }
            
            // Add category
            if ($postModel->category) {
                $keywordArray[] = $postModel->category->name;
            }
            
            // Add site name
            $keywordArray[] = config('app.name');
        } elseif ($category) {
            $keywordArray[] = $category->name;
            $keywordArray[] = config('app.name');
        }
        
        $keywords = !empty($keywordArray) ? implode(', ', array_unique($keywordArray)) : null;
    }
@endphp

@if ($keywords)
    <meta name="keywords" content="{{ $keywords }}">
@endif