<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\helpers\StringHelper;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\fieldlayoutelements\CustomField;
use craft\fields\PlainText;
use craft\fields\Date;
use craft\fields\Assets;
use craft\elements\Entry;
use craft\enums\PropagationMethod;

/**
 * m251018_110000_restructure_photography_and_freelance migration.
 */
class m251018_110000_restructure_photography_and_freelance extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        echo "\n=== Restructuring Photography and Creating Freelance Work ===\n\n";

        $entriesService = Craft::$app->getEntries();
        $fieldsService = Craft::$app->getFields();
        $sitesService = Craft::$app->getSites();
        $primarySite = $sitesService->getPrimarySite();

        // Step 1: Update Photography section to 3 levels and add Category entry type
        echo "📸 Updating Photography to 3-level hierarchy...\n";

        $photographySection = $entriesService->getSectionByHandle('photography');
        if ($photographySection) {
            // Create Category entry type
            $categoryEntryType = new EntryType([
                'name' => 'Category',
                'handle' => 'category',
                'uid' => StringHelper::UUID(),
            ]);

            if ($entriesService->saveEntryType($categoryEntryType)) {
                echo "  ✓ Created Category entry type\n";

                // Add field layout to Category
                $fieldLayout = $this->createFieldLayout([
                    'featuredImage',
                    'description',
                ]);
                $categoryEntryType->setFieldLayout($fieldLayout);
                $entriesService->saveEntryType($categoryEntryType);
                echo "    - Added field layout to Category\n";

                // Update section to 3 levels and add Category entry type
                $photographySection->maxLevels = 3; // Category (1) → Album (2) → Photo (3)

                $existingTypes = $entriesService->getEntryTypesBySectionId($photographySection->id);
                $existingTypes[] = $categoryEntryType;
                $photographySection->setEntryTypes($existingTypes);

                if ($entriesService->saveSection($photographySection)) {
                    echo "  ✓ Updated Photography to 3-level structure\n";
                }
            }
        }

        // Step 2: Create fields for Freelance Work
        echo "\n📋 Creating Freelance Work fields...\n";

        // Project Type field
        $projectTypeField = new PlainText([
            'name' => 'Project Type',
            'handle' => 'projectType',
        ]);
        if ($fieldsService->saveField($projectTypeField)) {
            echo "  ✓ Created Project Type field\n";
        }

        // Customer field
        $customerField = new PlainText([
            'name' => 'Customer',
            'handle' => 'customer',
        ]);
        if ($fieldsService->saveField($customerField)) {
            echo "  ✓ Created Customer field\n";
        }

        // Delivery Date field
        $deliveryDateField = new Date([
            'name' => 'Delivery Date',
            'handle' => 'deliveryDate',
        ]);
        if ($fieldsService->saveField($deliveryDateField)) {
            echo "  ✓ Created Delivery Date field\n";
        }

        // Project Images field (separate from gallery for more flexibility)
        $projectImagesField = new Assets([
            'name' => 'Project Images',
            'handle' => 'projectImages',
        ]);
        if ($fieldsService->saveField($projectImagesField)) {
            echo "  ✓ Created Project Images field\n";
        }

        // Step 3: Create Freelance Work section
        echo "\n💼 Creating Freelance Work section...\n";

        // Create Freelance Project entry type
        $freelanceProjectType = new EntryType([
            'name' => 'Freelance Project',
            'handle' => 'freelanceProject',
            'uid' => StringHelper::UUID(),
        ]);

        if (!$entriesService->saveEntryType($freelanceProjectType)) {
            echo "  ✗ Failed to create Freelance Project entry type\n";
            return false;
        }

        // Create the Freelance Work section as a channel
        $freelanceSection = new Section([
            'name' => 'Freelance Work',
            'handle' => 'freelanceWork',
            'type' => Section::TYPE_CHANNEL,
            'propagationMethod' => PropagationMethod::All,
        ]);

        $siteSettings = new Section_SiteSettings([
            'siteId' => $primarySite->id,
            'hasUrls' => true,
            'uriFormat' => 'freelance/{slug}',
            'template' => 'freelance/_entry',
            'enabledByDefault' => true,
        ]);

        $freelanceSection->setSiteSettings([$siteSettings]);
        $freelanceSection->setEntryTypes([$freelanceProjectType]);

        if ($entriesService->saveSection($freelanceSection)) {
            echo "  ✓ Freelance Work section created\n";

            // Add field layout to Freelance Project entry type
            $entryTypes = $entriesService->getEntryTypesBySectionId($freelanceSection->id);
            foreach ($entryTypes as $entryType) {
                if ($entryType->handle === 'freelanceProject') {
                    $fieldLayout = $this->createFieldLayout([
                        'featuredImage',
                        'projectType',
                        'customer',
                        'deliveryDate',
                        'description',
                        'projectImages',
                    ]);

                    $entryType->setFieldLayout($fieldLayout);
                    if ($entriesService->saveEntryType($entryType)) {
                        echo "    - Added field layout to Freelance Project\n";
                    }
                    break;
                }
            }
        } else {
            echo "  ✗ Failed to create Freelance Work section: " . json_encode($freelanceSection->getErrors()) . "\n";
            return false;
        }

        echo "\n✅ Photography and Freelance Work restructure complete!\n\n";

        return true;
    }

    /**
     * Create a field layout from field handles
     */
    private function createFieldLayout(array $fieldHandles): FieldLayout
    {
        $fieldsService = Craft::$app->getFields();

        $fieldLayout = new FieldLayout(['type' => Entry::class]);

        $tab = new FieldLayoutTab([
            'layout' => $fieldLayout,
            'name' => 'Content',
            'sortOrder' => 1,
        ]);

        $layoutElements = [];
        foreach ($fieldHandles as $handle) {
            $field = $fieldsService->getFieldByHandle($handle);
            if ($field) {
                $layoutElements[] = new CustomField($field);
            }
        }

        $tab->setElements($layoutElements);
        $fieldLayout->setTabs([$tab]);

        return $fieldLayout;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m251018_110000_restructure_photography_and_freelance cannot be reverted.\n";
        return false;
    }
}
