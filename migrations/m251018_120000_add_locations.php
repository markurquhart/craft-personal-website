<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\fields\Categories;
use craft\elements\Category;
use craft\enums\PropagationMethod;

/**
 * m251018_120000_add_locations migration.
 */
class m251018_120000_add_locations extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        echo "\n=== Adding Location System ===\n\n";

        $categoriesService = Craft::$app->getCategories();
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();
        $sitesService = Craft::$app->getSites();
        $primarySite = $sitesService->getPrimarySite();

        // Step 1: Create Locations category group
        echo "📍 Creating Locations category group...\n";

        $locationsGroup = new CategoryGroup([
            'name' => 'Locations',
            'handle' => 'locations',
            'maxLevels' => 3, // Country → State/Province → City
        ]);

        $siteSettings = new CategoryGroup_SiteSettings([
            'siteId' => $primarySite->id,
            'hasUrls' => true,
            'uriFormat' => 'locations/{slug}',
            'template' => 'locations/_category',
        ]);

        $locationsGroup->setSiteSettings([$primarySite->id => $siteSettings]);

        // Create a simple field layout for categories
        $fieldLayout = new FieldLayout(['type' => Category::class]);
        $tab = new FieldLayoutTab([
            'layout' => $fieldLayout,
            'name' => 'Content',
            'sortOrder' => 1,
        ]);
        $fieldLayout->setTabs([$tab]);
        $locationsGroup->setFieldLayout($fieldLayout);

        if ($categoriesService->saveGroup($locationsGroup)) {
            echo "  ✓ Locations category group created\n";
        } else {
            echo "  ✗ Failed to create Locations category group: " . json_encode($locationsGroup->getErrors()) . "\n";
            return false;
        }

        // Step 2: Create Locations field
        echo "\n📍 Creating Locations field...\n";

        $locationsField = new Categories([
            'name' => 'Locations',
            'handle' => 'locations',
            'source' => 'group:' . $locationsGroup->uid,
            'branchLimit' => null, // Allow selecting entire branches
        ]);

        if ($fieldsService->saveField($locationsField)) {
            echo "  ✓ Locations field created\n";
        } else {
            echo "  ✗ Failed to create Locations field\n";
            return false;
        }

        // Step 3: Add Locations field to Photography Albums
        echo "\n📸 Adding Locations to Photography Albums...\n";
        $photographySection = $entriesService->getSectionByHandle('photography');
        if ($photographySection) {
            $entryTypes = $entriesService->getEntryTypesBySectionId($photographySection->id);
            foreach ($entryTypes as $entryType) {
                if ($entryType->handle === 'album') {
                    $this->addLocationToFieldLayout($entryType, $locationsField);
                    if ($entriesService->saveEntryType($entryType)) {
                        echo "  ✓ Added Locations to Album entry type\n";
                    }
                    break;
                }
            }
        }

        // Step 4: Add Locations field to Freelance Work
        echo "\n💼 Adding Locations to Freelance Work...\n";
        $freelanceSection = $entriesService->getSectionByHandle('freelanceWork');
        if ($freelanceSection) {
            $entryTypes = $entriesService->getEntryTypesBySectionId($freelanceSection->id);
            foreach ($entryTypes as $entryType) {
                if ($entryType->handle === 'freelanceProject') {
                    $this->addLocationToFieldLayout($entryType, $locationsField);
                    if ($entriesService->saveEntryType($entryType)) {
                        echo "  ✓ Added Locations to Freelance Project entry type\n";
                    }
                    break;
                }
            }
        }

        // Step 5: Add Locations field to Thoughts
        echo "\n💭 Adding Locations to Thoughts...\n";
        $thoughtsSection = $entriesService->getSectionByHandle('thoughts');
        if ($thoughtsSection) {
            $entryTypes = $entriesService->getEntryTypesBySectionId($thoughtsSection->id);
            foreach ($entryTypes as $entryType) {
                if ($entryType->handle === 'blogPost') {
                    $this->addLocationToFieldLayout($entryType, $locationsField);
                    if ($entriesService->saveEntryType($entryType)) {
                        echo "  ✓ Added Locations to Blog Post entry type\n";
                    }
                    break;
                }
            }
        }

        // Step 6: Add Locations field to Travels
        echo "\n✈️  Adding Locations to Travels...\n";
        $travelsSection = $entriesService->getSectionByHandle('travels');
        if ($travelsSection) {
            $entryTypes = $entriesService->getEntryTypesBySectionId($travelsSection->id);
            foreach ($entryTypes as $entryType) {
                if ($entryType->handle === 'travelEntry') {
                    $this->addLocationToFieldLayout($entryType, $locationsField);
                    if ($entriesService->saveEntryType($entryType)) {
                        echo "  ✓ Added Locations to Travel Entry entry type\n";
                    }
                    break;
                }
            }
        }

        echo "\n✅ Location system complete!\n\n";

        return true;
    }

    /**
     * Add location field to an entry type's field layout
     */
    private function addLocationToFieldLayout($entryType, $locationsField): void
    {
        $layout = $entryType->getFieldLayout();
        if (!$layout) {
            return;
        }

        $tabs = $layout->getTabs();
        if (empty($tabs)) {
            return;
        }

        // Add to the first tab (usually "Content")
        $firstTab = $tabs[0];
        $elements = $firstTab->getElements();
        $elements[] = new \craft\fieldlayoutelements\CustomField($locationsField);
        $firstTab->setElements($elements);

        $layout->setTabs($tabs);
        $entryType->setFieldLayout($layout);
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m251018_120000_add_locations cannot be reverted.\n";
        return false;
    }
}
