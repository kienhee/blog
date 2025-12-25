<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HashTagSeeder extends Seeder
{
    private $tableName = 'hash_tags';

    private $version = 2;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (seed_version($this->tableName, $this->version, true)) {
            $hashtags = [
                // Công nghệ & Lập trình
                'Laravel',
                'PHP',
                'JavaScript',
                'React',
                'VueJS',
                'NodeJS',
                'Python',
                'Java',
                'CSharp',
                'TypeScript',
                'HTML5',
                'CSS3',
                'Bootstrap',
                'TailwindCSS',
                'MySQL',
                'PostgreSQL',
                'MongoDB',
                'Redis',
                'Docker',
                'Git',
                'GitHub',
                'API',
                'RESTful',
                'GraphQL',
                'Microservices',

                // Web Development
                'WebDev',
                'Frontend',
                'Backend',
                'FullStack',
                'Responsive',
                'MobileFirst',
                'PWA',
                'SEO',
                'Performance',
                'Security',

                // Cuộc sống & Cộng đồng
                'TechLife',
                'Developer',
                'Coding',
                'Programming',
                'Software',
                'Startup',
                'Innovation',
                'Digital',
                'Cloud',
                'AI',
                'MachineLearning',
                'DataScience',
                'Blockchain',
                'Cryptocurrency',
            ];

            // Tạo slug từ name và đảm bảo độ dài tối đa 20 ký tự
            $slugify = function ($name) {
                $slug = Str::slug($name);

                // Giới hạn độ dài slug tối đa 20 ký tự
                return Str::limit($slug, 20, '');
            };

            $data = [];
            foreach ($hashtags as $name) {
                $slug = $slugify($name);

                // Đảm bảo slug là unique
                $baseSlug = $slug;
                $counter = 1;
                while (DB::table($this->tableName)->where('slug', $slug)->exists()) {
                    $slug = Str::limit($baseSlug, 18, '').'-'.$counter;
                    $counter++;
                }

                $data[] = [
                    'name' => $name,
                    'slug' => $slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert in batches
            $chunks = array_chunk($data, 25);
            foreach ($chunks as $chunk) {
                DB::table($this->tableName)->insert($chunk);
            }
        }

    }
}
