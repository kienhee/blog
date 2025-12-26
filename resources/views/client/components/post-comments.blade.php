{{-- Comments Section --}}
@if (isset($post->allow_comment) && $post->allow_comment)
    <div class="post-comments">
        <h3 class="comments-title">Bình luận</h3>
        <div id="disqus_thread"></div>
        <script>
            (function() {
                var d = document,
                    s = d.createElement('script');
                s.src = 'https://YOUR_DISQUS_SHORTNAME.disqus.com/embed.js';
                s.setAttribute('data-timestamp', +new Date());
                (d.head || d.body).appendChild(s);
            })();
        </script>
        <noscript>Vui lòng bật JavaScript để xem bình luận.</noscript>
    </div>
@endif

