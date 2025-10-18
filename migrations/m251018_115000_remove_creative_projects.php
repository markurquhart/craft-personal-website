<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;

/**
 * m251018_115000_remove_creative_projects migration.
 */
class m251018_115000_remove_creative_projects extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        echo "\n=== Removing Creative Projects Section ===\n\n";

        $entriesService = Craft::$app->getEntries();
        $fieldsService = Craft::$app->getFields();

        // Step 1: Remove Creative Projects section
        echo "🗑️  Removing Creative Projects section...\n";
        $creativeSection = $entriesService->getSectionByHandle('creativeProjects');
        if ($creativeSection) {
            if ($entriesService->deleteSection($creativeSection)) {
                echo "  ✓ Creative Projects section removed\n";
            }
        }

        // Step 2: Remove Related Creative Projects field
        echo "\n🗑️  Removing Related Creative Projects field...\n";
        $relatedCreativeField = $fieldsService->getFieldByHandle('relatedCreativeProjects');
        if ($relatedCreativeField) {
            if ($fieldsService->deleteField($relatedCreativeField)) {
                echo "  ✓ Related Creative Projects field removed\n";
            }
        }

        // Step 3: Remove Creative Categories field (if it exists and is not used elsewhere)
        echo "\n🗑️  Removing Creative Categories...\n";
        $creativeCategoriesField = $fieldsService->getFieldByHandle('creativeCategories');
        if ($creativeCategoriesField) {
            if ($fieldsService->deleteField($creativeCategoriesField)) {
                echo "  ✓ Creative Categories field removed\n";
            }
        }

        // Step 4: Remove Creative Categories category group
        $categoriesService = Craft::$app->getCategories();
        $creativeCategoryGroup = $categoriesService->getGroupByHandle('creativeCategories');
        if ($creativeCategoryGroup) {
            if ($categoriesService->deleteGroup($creativeCategoryGroup)) {
                echo "  ✓ Creative Categories group removed\n";
            }
        }

        echo "\n✅ Creative Projects cleanup complete!\n\n";

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m251018_115000_remove_creative_projects cannot be reverted.\n";
        return false;
    }
}
