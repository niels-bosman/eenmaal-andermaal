<?php

namespace App\Core;

use App\Controllers\CategoryController;

/**
 * Class CategoryBar
 * @package App\Core
 */
class CategoryBar
{
    /**
     * @var
     */
    private $categories = [0 => [], 1 => [], 2 => [], 3 => [], 4 => []];
    
    /**
     * CategoryBar constructor.
     */
    public function __construct()
    {
        $this->setCategories();
    }
    
    /**
     * Renders the Category bar in a recursive way.
     * This method uses the getAll method listed below to gather its first basic data.
     * @param array $categories
     */
    public function render(array $categories = []): void
    {
        if (empty($categories)) {
            $keys = array_keys($this->categories);
            
            // Display only first 6 Categories.
            $categories = [
                $this->categories[$keys[0]],
                $this->categories[$keys[1]],
                $this->categories[$keys[2]],
                $this->categories[$keys[3]],
                $this->categories[$keys[4]],
                $this->categories[$keys[5]],
            ];
        }
        
        echo "<ul class='a-category-bar__list'>";
        
        foreach ($categories as $id => $category) {
            echo "<li class='a-category-bar__list-item'>";
            echo "<a href='/veilingen?categorie=$id' class='a-category-bar__link'>";
            echo $category['name'];
            echo "</a>";
            
            if (!empty($category['children'])) {
                self::render($category['children']);
            }
            
            echo "</li>";
        }
        
        echo "</ul>";
    }
    
    /**
     * This method regulates the creation of the categories based on
     * the index method of the category controller.
     * @return void All Categories
     */
    public function setCategories(): void
    {
        $cc = new CategoryController;
        
        $this->categories = $this->formatPerLevel($cc->index(), $this->categories);
        $this->categories = $this->formatAssociative($this->categories);
    }
    
    /**
     * Formats the queried data per level and removes the duplicates
     * @param $columns
     * @param $new
     * @return array
     */
    private function formatPerLevel($columns, $new): array
    {
        foreach ($columns as $column) {
            $levels = [
                [$column['Level1ID'], $column['Level1Name']],
                [$column['Level2ID'], $column['Level2Name']],
                [$column['Level3ID'], $column['Level3Name']],
                [$column['Level4ID'], $column['Level4Name']],
                [$column['Level5ID'], $column['Level5Name']],
            ];
            
            foreach ($levels as $index => $level) {
                [$id, $name] = $level;
                
                if (!$id || !$name) {
                    continue;
                }
                
                // If the Level does not exists, add it and initialize the children array.
                if (!array_key_exists($id, $new[$index])) {
                    $new[$index][$id] = ['name' => $name, 'children' => []];
                }
                
                // If the child is already initialized, don't add it again.
                if (array_key_exists($levels[$index + 1][0], $new[$index][$id]['children'])) {
                    continue;
                }
                
                [$childId, $childName] = $levels[$index + 1];
                
                // Here we add the children (if found) to the children property.
                $new[$index][$id]['children'][$childId] = ['name' => $childName, 'children' => []];
            }
        }
        
        return $new;
    }
    
    /**
     * Formats all the inputted array from by-level to an associative array.
     * @param array $levels
     * @return array
     */
    private function formatAssociative(array $levels): array
    {
        // Unset levels if fully empty. We do this because otherwise
        // the count in the for loop won't output the desired amount.
        foreach ($levels as $id => $level) {
            if (empty($level)) {
                unset($levels[$id]);
            }
        }
        
        // Loop through the levels, start at the second last index, stop with the first.
        for ($i = count($levels) - 2; $i >= 0; $i--) {
            foreach ($levels[$i] as $itemId => $item) {
                foreach ($item['children'] as $childId => $child) {
                    // If child ID is set, copy it to this iteration in array.
                    if (isset($levels[$i + 1][$childId])) {
                        $levels[$i][$itemId]['children'][$childId] = $levels[$i + 1][$childId];
                    }
                }
            }
        }
        
        return $levels[0];
    }
}
