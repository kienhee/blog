{{-- Floating TOC Button (Mobile Only) --}}
<button class="floating-toc-btn" id="floatingTocBtn" type="button" title="Mục lục">
    <i class="bx bx-list-ul"></i>
</button>

{{-- Offcanvas for TOC (Mobile) --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="tocOffcanvas" aria-labelledby="tocOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="tocOffcanvasLabel">
            <i class="bx bx-list-ul me-2"></i>
            Mục lục
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="toc-list" id="toc-offcanvas-list">
            {{-- TOC will be copied here by JavaScript --}}
        </ul>
    </div>
</div>

<style>
    /* Floating TOC Button - Mobile Only */
    .floating-toc-btn {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #667eea;
        color: #fff;
        border: none;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        cursor: pointer;
        z-index: 1000;
        transition: all 0.3s ease;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .floating-toc-btn:hover {
        background: #5568d3;
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        transform: translateY(-2px);
    }

    .floating-toc-btn:active {
        transform: translateY(0);
    }

    .floating-toc-btn i {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Show on mobile only */
    @media (max-width: 991.98px) {
        .floating-toc-btn {
            display: flex;
        }
    }

    /* Offcanvas TOC Styles */
    #toc-offcanvas-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    #toc-offcanvas-list .toc-item {
        margin-bottom: 0.5rem;
    }

    #toc-offcanvas-list .toc-link {
        display: block;
        padding: 0.75rem;
        color: #212529;
        text-decoration: none;
        border-radius: 0.375rem;
        transition: all 0.2s ease;
    }

    #toc-offcanvas-list .toc-link:hover {
        background: #f8f9fa;
        color: #667eea;
    }

    #toc-offcanvas-list .toc-item.level-3 {
        padding-left: 1.5rem;
    }

    #toc-offcanvas-list .toc-item.level-4 {
        padding-left: 3rem;
    }

    /* Dark mode styles for offcanvas */
    .dark-style #toc-offcanvas-list .toc-link,
    html.dark-style #toc-offcanvas-list .toc-link,
    body.dark-style #toc-offcanvas-list .toc-link,
    html[data-theme="dark"] #toc-offcanvas-list .toc-link,
    [data-theme="dark"] #toc-offcanvas-list .toc-link {
        color: #cbcbe2;
    }

    .dark-style #toc-offcanvas-list .toc-link:hover,
    html.dark-style #toc-offcanvas-list .toc-link:hover,
    body.dark-style #toc-offcanvas-list .toc-link:hover,
    html[data-theme="dark"] #toc-offcanvas-list .toc-link:hover,
    [data-theme="dark"] #toc-offcanvas-list .toc-link:hover {
        background: #3a3b5c;
        color: #8b9aff;
    }
</style>
