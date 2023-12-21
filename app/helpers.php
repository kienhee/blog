<?php

use App\Models\Category;

use App\Models\Group;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Trending;
use App\Models\User;

function getAllGroups()
{
    return Group::all();
}
function getAllCategories($type = "1")
{
    return Category::where('type', $type)->get();
}
function getAllProjects()
{
    return Project::all();
}
function
getAllTags()
{
    return Tag::all();
}
function estimateReadingTime($text, $wpm = 200)
{
    $totalWords = str_word_count(strip_tags($text));
    $minutes = floor($totalWords / $wpm);
    return $minutes;
}
function menuSelect($menu, $parent = 0, $level = 0)
{

    if ($menu->count() > 0) {
        $result = [];
        foreach ($menu as $key => $category) {
            if ($category['category_id'] == $parent) {
                $category['level'] = $level;
                $result[] = $category;
                unset($menu[$category['id']]);
                $child = menuSelect($menu, $category['id'], $level + 1);
                $result = array_merge($result, $child);
            }
        }
        return $result;
    }
}

function menuTreeCategory($menu, $parentId = 0)
{
    if ($menu->count() > 0) {
        foreach ($menu as $key => $category) {
            if ($category['category_id'] == $parentId) {
                echo '<li><a class="d-flex justify-content-between" href ="' . route('dashboard.category.edit', $category['id']) . '" title="Click Read more"> <span>' . $category['name'] . '</span> </a>';
                echo '<ul >';
                unset($menu[$category['id']]);
                echo menuTreeCategory($menu, $category['id']);
                echo "</ul>";
                echo "</li>";
                echo "</li>";
            }
        }
    }
}


function trendExist($trending, $checkID)
{
    return $trending->contains('project_id', $checkID);
}

function author()
{
    return User::where('id', 1)->first();
}
