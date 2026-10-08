-- Danh mục và hashtag cho dữ liệu demo (chỉ dev/demo, không bao giờ nằm trong db/migration).
-- 9 danh mục cha, 28 danh mục con, tất cả đều hiển thị; bài viết nằm ở danh mục con.

INSERT INTO categories (name, slug, description, parent_id, visible) VALUES
 ('Trí tuệ nhân tạo', 'ai', 'Mô hình ngôn ngữ, học máy, prompt và ứng dụng AI trong công việc lập trình', NULL, 1),
 ('Phát triển Web', 'web', 'Frontend, backend, CSS và hiệu năng cho các sản phẩm web hiện đại', NULL, 1),
 ('Phát triển Mobile', 'mobile', 'Ứng dụng di động native và đa nền tảng', NULL, 1),
 ('DevOps & Cloud', 'devops', 'Container, CI/CD, hạ tầng như mã nguồn và điện toán đám mây', NULL, 1),
 ('Dữ liệu', 'data', 'Cơ sở dữ liệu, SQL và kỹ thuật dữ liệu', NULL, 1),
 ('An toàn thông tin', 'security', 'Bảo mật ứng dụng và những thói quen an toàn cho lập trình viên', NULL, 1),
 ('Ngôn ngữ lập trình', 'languages', 'Đi sâu vào từng ngôn ngữ: cú pháp, thói quen tốt và so sánh', NULL, 1),
 ('Sự nghiệp & Học tập', 'career', 'Phỏng vấn, làm việc từ xa và lộ trình phát triển nghề nghiệp', NULL, 1),
 ('Công cụ & Năng suất', 'tools', 'Công cụ, dòng lệnh và quy trình giúp làm việc nhanh hơn', NULL, 1);

INSERT INTO categories (name, slug, description, parent_id, visible)
SELECT v.name, v.slug, v.description, p.id, 1 FROM (
  SELECT 'Mô hình ngôn ngữ lớn' AS name, 'ai-llm' AS slug, 'Cách LLM hoạt động, so sánh mô hình và giới hạn cần biết' AS description, 'ai' AS parent UNION ALL
  SELECT 'Học máy', 'ai-machine-learning', 'Nền tảng học máy, huấn luyện và đánh giá mô hình', 'ai' UNION ALL
  SELECT 'Prompt Engineering', 'ai-prompt-engineering', 'Viết prompt rõ ràng để nhận câu trả lời dùng được ngay', 'ai' UNION ALL
  SELECT 'AI Agents', 'ai-agents', 'Xây dựng tác tử AI biết dùng công cụ và tự hoàn thành tác vụ', 'ai' UNION ALL
  SELECT 'Frontend', 'web-frontend', 'React, Vue, Next.js và những gì chạy trên trình duyệt', 'web' UNION ALL
  SELECT 'Backend & API', 'web-backend', 'Thiết kế API, xác thực, xử lý nền và kiến trúc server', 'web' UNION ALL
  SELECT 'CSS & Design System', 'web-css', 'Layout hiện đại, token thiết kế và hệ thống giao diện nhất quán', 'web' UNION ALL
  SELECT 'Hiệu năng Web', 'web-performance', 'Core Web Vitals, tải trang nhanh và đo lường thực tế', 'web' UNION ALL
  SELECT 'Android', 'mobile-android', 'Kotlin, Jetpack Compose và kiến trúc ứng dụng Android', 'mobile' UNION ALL
  SELECT 'iOS', 'mobile-ios', 'Swift, SwiftUI và phát hành ứng dụng lên App Store', 'mobile' UNION ALL
  SELECT 'Flutter', 'mobile-flutter', 'Một codebase Dart cho cả Android và iOS', 'mobile' UNION ALL
  SELECT 'Docker & Kubernetes', 'devops-containers', 'Đóng gói, triển khai và vận hành container', 'devops' UNION ALL
  SELECT 'CI/CD', 'devops-ci-cd', 'Pipeline build, test và triển khai tự động', 'devops' UNION ALL
  SELECT 'AWS', 'devops-aws', 'Các dịch vụ AWS thường dùng và cách tiết kiệm chi phí', 'devops' UNION ALL
  SELECT 'Infrastructure as Code', 'devops-iac', 'Terraform và quản lý hạ tầng bằng mã nguồn', 'devops' UNION ALL
  SELECT 'SQL & Cơ sở dữ liệu', 'data-sql', 'PostgreSQL, MySQL, tối ưu truy vấn và thiết kế schema', 'data' UNION ALL
  SELECT 'Kỹ thuật dữ liệu', 'data-engineering', 'Pipeline dữ liệu, ETL và xử lý dữ liệu quy mô lớn', 'data' UNION ALL
  SELECT 'Bảo mật ứng dụng', 'security-appsec', 'OWASP, xác thực an toàn và các lỗ hổng thường gặp', 'security' UNION ALL
  SELECT 'Mật mã & Quyền riêng tư', 'security-crypto', 'Mã hóa, băm mật khẩu và bảo vệ dữ liệu người dùng', 'security' UNION ALL
  SELECT 'Python', 'languages-python', 'Python từ cơ bản đến thực hành tốt', 'languages' UNION ALL
  SELECT 'JavaScript & TypeScript', 'languages-js-ts', 'Ngôn ngữ của web và hệ thống kiểu của TypeScript', 'languages' UNION ALL
  SELECT 'Java', 'languages-java', 'Java hiện đại và hệ sinh thái Spring', 'languages' UNION ALL
  SELECT 'Go', 'languages-go', 'Concurrency đơn giản và công cụ dòng lệnh nhanh với Go', 'languages' UNION ALL
  SELECT 'Rust', 'languages-rust', 'An toàn bộ nhớ và hiệu năng với Rust', 'languages' UNION ALL
  SELECT 'Phỏng vấn kỹ thuật', 'career-interviews', 'Chuẩn bị phỏng vấn, giải thuật và câu hỏi thiết kế hệ thống', 'career' UNION ALL
  SELECT 'Làm việc từ xa', 'career-remote', 'Giao tiếp, năng suất và cân bằng khi làm việc từ xa', 'career' UNION ALL
  SELECT 'Git & Công cụ dev', 'tools-git', 'Git, editor và những thủ thuật tiết kiệm thời gian mỗi ngày', 'tools' UNION ALL
  SELECT 'Linux & Terminal', 'tools-linux', 'Dòng lệnh, shell script và quản trị Linux cơ bản', 'tools'
) v JOIN categories p ON p.slug = v.parent;

INSERT INTO hashtags (name, slug, description, active) VALUES
 ('ChatGPT', 'chatgpt', 'Bài viết gắn thẻ ChatGPT', 1),
 ('Claude', 'claude', 'Bài viết gắn thẻ Claude', 1),
 ('Gemini', 'gemini', 'Bài viết gắn thẻ Gemini', 1),
 ('LLM', 'llm', 'Bài viết gắn thẻ LLM', 1),
 ('Prompt Engineering', 'prompt-engineering', 'Bài viết gắn thẻ Prompt Engineering', 1),
 ('AI Agents', 'ai-agents', 'Bài viết gắn thẻ AI Agents', 1),
 ('RAG', 'rag', 'Bài viết gắn thẻ RAG', 1),
 ('Machine Learning', 'machine-learning', 'Bài viết gắn thẻ Machine Learning', 1),
 ('Deep Learning', 'deep-learning', 'Bài viết gắn thẻ Deep Learning', 1),
 ('Fine Tuning', 'fine-tuning', 'Bài viết gắn thẻ Fine Tuning', 1),
 ('JavaScript', 'javascript', 'Bài viết gắn thẻ JavaScript', 1),
 ('TypeScript', 'typescript', 'Bài viết gắn thẻ TypeScript', 1),
 ('Python', 'python', 'Bài viết gắn thẻ Python', 1),
 ('Rust', 'rust', 'Bài viết gắn thẻ Rust', 1),
 ('Go', 'go', 'Bài viết gắn thẻ Go', 1),
 ('Java', 'java', 'Bài viết gắn thẻ Java', 1),
 ('Kotlin', 'kotlin', 'Bài viết gắn thẻ Kotlin', 1),
 ('Swift', 'swift', 'Bài viết gắn thẻ Swift', 1),
 ('Dart', 'dart', 'Bài viết gắn thẻ Dart', 1),
 ('React', 'react', 'Bài viết gắn thẻ React', 1),
 ('Vue', 'vue', 'Bài viết gắn thẻ Vue', 1),
 ('Next.js', 'next-js', 'Bài viết gắn thẻ Next.js', 1),
 ('Node.js', 'node-js', 'Bài viết gắn thẻ Node.js', 1),
 ('Spring Boot', 'spring-boot', 'Bài viết gắn thẻ Spring Boot', 1),
 ('HTML & CSS', 'html-css', 'Bài viết gắn thẻ HTML & CSS', 1),
 ('Tailwind CSS', 'tailwind-css', 'Bài viết gắn thẻ Tailwind CSS', 1),
 ('Web Performance', 'web-performance', 'Bài viết gắn thẻ Web Performance', 1),
 ('REST API', 'rest-api', 'Bài viết gắn thẻ REST API', 1),
 ('Android', 'android', 'Bài viết gắn thẻ Android', 1),
 ('iOS', 'ios', 'Bài viết gắn thẻ iOS', 1),
 ('Flutter', 'flutter', 'Bài viết gắn thẻ Flutter', 1),
 ('Docker', 'docker', 'Bài viết gắn thẻ Docker', 1),
 ('Kubernetes', 'kubernetes', 'Bài viết gắn thẻ Kubernetes', 1),
 ('Terraform', 'terraform', 'Bài viết gắn thẻ Terraform', 1),
 ('AWS', 'aws', 'Bài viết gắn thẻ AWS', 1),
 ('CI/CD', 'ci-cd', 'Bài viết gắn thẻ CI/CD', 1),
 ('GitHub Actions', 'github-actions', 'Bài viết gắn thẻ GitHub Actions', 1),
 ('SQL', 'sql', 'Bài viết gắn thẻ SQL', 1),
 ('PostgreSQL', 'postgresql', 'Bài viết gắn thẻ PostgreSQL', 1),
 ('MySQL', 'mysql', 'Bài viết gắn thẻ MySQL', 1),
 ('MongoDB', 'mongodb', 'Bài viết gắn thẻ MongoDB', 1),
 ('Redis', 'redis', 'Bài viết gắn thẻ Redis', 1),
 ('Data Engineering', 'data-engineering', 'Bài viết gắn thẻ Data Engineering', 1),
 ('Cybersecurity', 'cybersecurity', 'Bài viết gắn thẻ Cybersecurity', 1),
 ('OWASP', 'owasp', 'Bài viết gắn thẻ OWASP', 1),
 ('Encryption', 'encryption', 'Bài viết gắn thẻ Encryption', 1),
 ('Git', 'git', 'Bài viết gắn thẻ Git', 1),
 ('Linux', 'linux', 'Bài viết gắn thẻ Linux', 1),
 ('Clean Code', 'clean-code', 'Bài viết gắn thẻ Clean Code', 1),
 ('Testing', 'testing', 'Bài viết gắn thẻ Testing', 1),
 ('Open Source', 'open-source', 'Bài viết gắn thẻ Open Source', 1),
 ('Phỏng vấn', 'phong-van', 'Bài viết gắn thẻ Phỏng vấn', 1),
 ('Remote Work', 'remote-work', 'Bài viết gắn thẻ Remote Work', 1),
 ('Career Growth', 'career-growth', 'Bài viết gắn thẻ Career Growth', 1);
