-- Seed fake posts for testing (demo/dev only)

-- Ensure a seed author exists (password: admin123). The demo seeds run after every schema migration,
-- so the role has to be set here: an account without one cannot open the admin panel.
INSERT INTO users (full_name, email, password, role_id, status)
SELECT 'Admin User', 'admin@kienhee.com', '$2a$10$3VolJvGzHsoZn3MseGEfQebqRezmpfj4nNErmXxGFwLvIKCHbIwIq',
       (SELECT id FROM roles WHERE slug = 'admin'), 'ACTIVE'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@kienhee.com');

INSERT INTO posts (title, slug, excerpt, content, cover_image, status, category_id, author_id, seo_title, seo_description, published_at)
VALUES (
    'How to brief an AI tool so the first answer is usable',
    'brief-an-ai-tool',
    'A short checklist for writing prompts that get you a usable answer on the first try.',
    '<p>Most people meet an AI tool the same way: they open it, type a question, and get something almost useful.</p><p>Give the model context, a clear goal, and the shape of the answer you want, and the first response gets a lot closer to done.</p>',
    NULL,
    'PUBLISHED',
    (SELECT id FROM categories WHERE slug = 'artificial-intelligence-prompt-engineering'),
    (SELECT id FROM users WHERE email = 'admin@kienhee.com'),
    'Brief an AI tool for a usable first answer',
    'A short checklist for writing prompts that get a usable answer on the first try.',
    DATE_SUB(NOW(), INTERVAL 2 DAY)
);

INSERT INTO posts (title, slug, excerpt, content, cover_image, status, category_id, author_id, seo_title, seo_description, published_at)
VALUES (
    'What changed in the big assistants this week',
    'assistants-week-37',
    'A short recap of what shipped across the major assistants this week.',
    '<p>Every week the big assistants ship small changes that add up. Here is what actually mattered this week.</p>',
    NULL,
    'PUBLISHED',
    (SELECT id FROM categories WHERE slug = 'artificial-intelligence'),
    (SELECT id FROM users WHERE email = 'admin@kienhee.com'),
    'This week in AI assistants',
    'A short recap of what shipped across the major assistants this week.',
    DATE_SUB(NOW(), INTERVAL 4 DAY)
);

INSERT INTO posts (title, slug, excerpt, content, cover_image, status, category_id, author_id, seo_title, seo_description, published_at)
VALUES (
    'Six prompt patterns that survive a model upgrade',
    'durable-prompt-patterns',
    'Patterns that keep working even after the underlying model changes.',
    '<p>Model upgrades break brittle prompts. These six patterns have held up across several upgrades.</p><ul><li>Be explicit about the output format</li><li>State constraints before examples</li><li>Ask for reasoning only when you will read it</li></ul>',
    NULL,
    'PUBLISHED',
    (SELECT id FROM categories WHERE slug = 'artificial-intelligence-prompt-engineering'),
    (SELECT id FROM users WHERE email = 'admin@kienhee.com'),
    'Prompt patterns that survive a model upgrade',
    'Reusable prompt patterns that keep working even after the underlying model changes.',
    DATE_SUB(NOW(), INTERVAL 8 DAY)
);

INSERT INTO posts (title, slug, excerpt, content, cover_image, status, category_id, author_id, seo_title, seo_description, published_at)
VALUES (
    'Picking between a chat app, an agent, and a script',
    'chat-agent-or-script',
    'A quick decision guide for choosing the right shape of AI tool for the job.',
    '<p>Not every problem needs an agent. Sometimes a chat window is enough, and sometimes you just need a script.</p>',
    NULL,
    'DRAFT',
    (SELECT id FROM categories WHERE slug = 'artificial-intelligence-ai-agents'),
    (SELECT id FROM users WHERE email = 'admin@kienhee.com'),
    NULL,
    NULL,
    NULL
);

INSERT INTO posts (title, slug, excerpt, content, cover_image, status, category_id, author_id, seo_title, seo_description, published_at)
VALUES (
    'Adding an AI feature without rewriting your backend',
    'ai-feature-no-rewrite',
    'How to bolt an AI feature onto an existing backend without a rewrite.',
    '<p>You do not need to rewrite your backend to add an AI feature. In most cases a single new endpoint is enough.</p>',
    NULL,
    'SCHEDULED',
    (SELECT id FROM categories WHERE slug = 'web-development-backend-apis'),
    (SELECT id FROM users WHERE email = 'admin@kienhee.com'),
    'Add an AI feature without a backend rewrite',
    'How to bolt an AI feature onto an existing backend without a full rewrite.',
    NULL
);

INSERT INTO posts (title, slug, excerpt, content, cover_image, status, category_id, author_id, seo_title, seo_description, published_at)
VALUES (
    'Automating a weekly report end to end',
    'weekly-report-automation',
    'A walkthrough of automating a recurring weekly report from data pull to delivery.',
    '<p>This is the exact pipeline we use to generate and send a weekly report without touching a spreadsheet.</p>',
    NULL,
    'PUBLISHED',
    (SELECT id FROM categories WHERE slug = 'productivity-automation-tools'),
    (SELECT id FROM users WHERE email = 'admin@kienhee.com'),
    'Automate a weekly report end to end',
    'A walkthrough of automating a recurring weekly report from data pull to delivery.',
    DATE_SUB(NOW(), INTERVAL 20 DAY)
);

INSERT INTO posts (title, slug, excerpt, content, cover_image, status, category_id, author_id, seo_title, seo_description, published_at)
VALUES (
    'Why your AI output reads like AI',
    'output-reads-like-ai',
    'The small habits that make generated text sound generated, and how to fix them.',
    '<p>There is a specific rhythm to unedited AI output. Once you notice it, you cannot unsee it.</p>',
    NULL,
    'PUBLISHED',
    (SELECT id FROM categories WHERE slug = 'artificial-intelligence-prompt-engineering'),
    (SELECT id FROM users WHERE email = 'admin@kienhee.com'),
    'Why your AI output reads like AI',
    'The small habits that make generated text sound generated, and how to fix them.',
    DATE_SUB(NOW(), INTERVAL 24 DAY)
);

INSERT INTO posts (title, slug, excerpt, content, cover_image, status, category_id, author_id, seo_title, seo_description, published_at)
VALUES (
    'Open models caught up on everyday tasks',
    'open-models-caught-up',
    'A look at how far open models have come on the tasks people actually use daily.',
    '<p>For most everyday tasks, the gap between open and closed models has nearly closed.</p>',
    NULL,
    'ARCHIVED',
    (SELECT id FROM categories WHERE slug = 'artificial-intelligence'),
    (SELECT id FROM users WHERE email = 'admin@kienhee.com'),
    NULL,
    NULL,
    DATE_SUB(NOW(), INTERVAL 30 DAY)
);

INSERT INTO posts (title, slug, excerpt, content, cover_image, status, category_id, author_id, seo_title, seo_description, published_at)
VALUES (
    'A checklist before you ship an AI feature',
    'ai-ship-checklist',
    'Things worth checking before an AI feature goes in front of real users.',
    '<p>Before shipping, walk through failure modes, cost per request, and what happens when the model is wrong.</p>',
    NULL,
    'DRAFT',
    (SELECT id FROM categories WHERE slug = 'artificial-intelligence-ai-agents'),
    (SELECT id FROM users WHERE email = 'admin@kienhee.com'),
    NULL,
    NULL,
    NULL
);

INSERT INTO posts (title, slug, excerpt, content, cover_image, status, category_id, author_id, seo_title, seo_description, published_at)
VALUES (
    'Three ways to cut token cost without quality loss',
    'cut-token-cost',
    'Three practical changes that reduce token spend without hurting output quality.',
    '<p>Token cost adds up fast at scale. These three changes cut cost without a noticeable quality drop.</p>',
    NULL,
    'PUBLISHED',
    (SELECT id FROM categories WHERE slug = 'artificial-intelligence-machine-learning'),
    (SELECT id FROM users WHERE email = 'admin@kienhee.com'),
    'Cut token cost without losing quality',
    'Three practical changes that reduce token spend without hurting output quality.',
    DATE_SUB(NOW(), INTERVAL 34 DAY)
);

-- Hashtag associations
INSERT INTO post_hashtags (post_id, hashtag_id)
SELECT p.id, h.id FROM posts p, hashtags h WHERE p.slug = 'brief-an-ai-tool' AND h.slug IN ('prompt-engineering', 'chatgpt');

INSERT INTO post_hashtags (post_id, hashtag_id)
SELECT p.id, h.id FROM posts p, hashtags h WHERE p.slug = 'assistants-week-37' AND h.slug IN ('chatgpt', 'claude', 'gemini');

INSERT INTO post_hashtags (post_id, hashtag_id)
SELECT p.id, h.id FROM posts p, hashtags h WHERE p.slug = 'durable-prompt-patterns' AND h.slug IN ('prompt-engineering', 'llm');

INSERT INTO post_hashtags (post_id, hashtag_id)
SELECT p.id, h.id FROM posts p, hashtags h WHERE p.slug = 'chat-agent-or-script' AND h.slug IN ('ai-agents');

INSERT INTO post_hashtags (post_id, hashtag_id)
SELECT p.id, h.id FROM posts p, hashtags h WHERE p.slug = 'ai-feature-no-rewrite' AND h.slug IN ('spring-boot', 'rag');

INSERT INTO post_hashtags (post_id, hashtag_id)
SELECT p.id, h.id FROM posts p, hashtags h WHERE p.slug = 'weekly-report-automation' AND h.slug IN ('product-management');

INSERT INTO post_hashtags (post_id, hashtag_id)
SELECT p.id, h.id FROM posts p, hashtags h WHERE p.slug = 'output-reads-like-ai' AND h.slug IN ('prompt-engineering', 'llm');

INSERT INTO post_hashtags (post_id, hashtag_id)
SELECT p.id, h.id FROM posts p, hashtags h WHERE p.slug = 'open-models-caught-up' AND h.slug IN ('open-source', 'llm');

INSERT INTO post_hashtags (post_id, hashtag_id)
SELECT p.id, h.id FROM posts p, hashtags h WHERE p.slug = 'ai-ship-checklist' AND h.slug IN ('ai-agents', 'cybersecurity');

INSERT INTO post_hashtags (post_id, hashtag_id)
SELECT p.id, h.id FROM posts p, hashtags h WHERE p.slug = 'cut-token-cost' AND h.slug IN ('machine-learning', 'llm');
