<?php 


function getParentCats($cats, &$parent_cats)
{
    if(empty($cats)) return $parent_cats;
    $parent_cats[] = array("cat_id"=>(int)$cats->category["category_id"], "name"=>(string)$cats->category->name);
    
    if(isset($cats->category->child_categories))
    {
        getParentCats($cats->category->child_categories, $parent_cats);
    }
    
    return $parent_cats;
}