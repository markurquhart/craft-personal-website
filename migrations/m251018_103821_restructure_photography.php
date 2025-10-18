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
use craft\models\Structure;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Entries;
use craft\elements\Entry;
use craft\enums\PropagationMethod;

/**
 * m251018_103821_restructure_photography migration.
 */
class m251018_103821_restructure_photography extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        echo "\n=== Restructuring Photography Content ===\n\n";

        $entriesService = Craft::$app->getEntries();
        $fieldsService = Craft::$app->getFields();
        $sitesService = Craft::$app->getSites();
        $primarySite = $sitesService->getPrimarySite();

        // Step 1: Remove photographyProject entry type from Creative Projects
        echo "🗑️  Removing Photography Project from Creative Projects...\n";
        $creativeSection = $entriesService->getSectionByHandle('creativeProjects');
        if ($creativeSection) {
            $entryTypes = $entriesService->getEntryTypesBySectionId($creativeSection->id);
            foreach ($entryTypes as $entryType) {
                if ($entryType->handle === 'photographyProject') {
                    $entriesService->deleteEntryType($entryType);
                    echo "  ✓ Removed Photography Project entry type\n";
                    break;
                }
            }
        }

        // Step 2: Add "Other" entry type to Creative Projects
        echo "\n➕ Adding 'Other' entry type to Creative Projects...\n";
        $otherEntryType = new EntryType([
            'name' => 'Other',
            'handle' => 'other',
            'uid' => StringHelper::UUID(),
        ]);

        if ($entriesService->saveEntryType($otherEntryType)) {
            echo "  ✓ Created 'Other' entry type\n";

            // Add field layout
            $fieldLayout = $this->createFieldLayout([
                'featuredImage',
                'gallery',
                'description',
                'client',
                'projectDate',
                'creativeCategories',
            ]);
            $otherEntryType->setFieldLayout($fieldLayout);
            $entriesService->saveEntryType($otherEntryType);

            // Add to Creative Projects section
            if ($creativeSection) {
                $existingTypes = $entriesService->getEntryTypesBySectionId($creativeSection->id);
                $existingTypes[] = $otherEntryType;
                $creativeSection->setEntryTypes($existingTypes);
                $entriesService->saveSection($creativeSection);
                echo "  ✓ Added 'Other' to Creative Projects section\n";
            }
        }

        // Step 3: Create Photography structure section
        echo "\n📸 Creating Photography section...\n";

        // Create Album entry type first
        $albumEntryType = new EntryType([
            'name' => 'Album',
            'handle' => 'album',
            'uid' => StringHelper::UUID(),
        ]);

        if (!$entriesService->saveEntryType($albumEntryType)) {
            echo "  ✗ Failed to create Album entry type\n";
            return false;
        }

        // Create Photo entry type
        $photoEntryType = new EntryType([
            'name' => 'Photo',
            'handle' => 'photo',
            'uid' => StringHelper::UUID(),
        ]);

        if (!$entriesService->saveEntryType($photoEntryType)) {
            echo "  ✗ Failed to create Photo entry type\n";
            return false;
        }

        // Create the Photography section as a structure
        $photographySection = new Section([
            'name' => 'Photography',
            'handle' => 'photography',
            'type' => Section::TYPE_STRUCTURE,
            'propagationMethod' => PropagationMethod::All,
            'maxLevels' => 2, // Album (level 1) -> Photos (level 2)
        ]);

        $siteSettings = new Section_SiteSettings([
            'siteId' => $primarySite->id,
            'hasUrls' => true,
            'uriFormat' => 'photography/{slug}',
            'template' => 'photography/_entry',
            'enabledByDefault' => true,
        ]);

        $photographySection->setSiteSettings([$siteSettings]);
        $photographySection->setEntryTypes([$albumEntryType, $photoEntryType]);

        if ($entriesService->saveSection($photographySection)) {
            echo "  ✓ Photography section created\n";

            // Add field layouts to entry types
            $this->addAlbumFieldLayout($photographySection);
            $this->addPhotoFieldLayout($photographySection);
        } else {
            echo "  ✗ Failed to create Photography section: " . json_encode($photographySection->getErrors()) . "\n";
            return false;
        }

        // Step 4: Create relationship field for Photography Albums
        echo "\n🔗 Creating Photography relationship fields...\n";

        $relatedPhotographyField = new Entries([
            'name' => 'Related Photography Albums',
            'handle' => 'relatedPhotographyAlbums',
            'sources' => ['section:' . $photographySection->uid],
        ]);

        if ($fieldsService->saveField($relatedPhotographyField)) {
            echo "  ✓ Related Photography Albums field created\n";

            // Add to Travels and Thoughts sections
            $this->addPhotographyRelationshipsToSections();
        }

        echo "\n✅ Photography restructure complete!\n\n";

        return true;
    }

    /**
     * Add field layout to Album entry type
     */
    private function addAlbumFieldLayout(Section $section): void
    {
        $entriesService = Craft::$app->getEntries();
        $entryTypes = $entriesService->getEntryTypesBySectionId($section->id);

        foreach ($entryTypes as $entryType) {
            if ($entryType->handle === 'album') {
                $fieldLayout = $this->createFieldLayout([
                    'featuredImage',
                    'description',
                    'photoLocation',
                    'dateTaken',
                ]);

                $entryType->setFieldLayout($fieldLayout);
                if ($entriesService->saveEntryType($entryType)) {
                    echo "    - Added field layout to Album\n";
                }
                break;
            }
        }
    }

    /**
     * Add field layout to Photo entry type
     */
    private function addPhotoFieldLayout(Section $section): void
    {
        $entriesService = Craft::$app->getEntries();
        $entryTypes = $entriesService->getEntryTypesBySectionId($section->id);

        foreach ($entryTypes as $entryType) {
            if ($entryType->handle === 'photo') {
                $fieldLayout = $this->createFieldLayout([
                    'featuredImage',
                    'description',
                    'dateTaken',
                    'photoLocation',
                    'camera',
                    'lens',
                    'iso',
                    'aperture',
                    'shutterSpeed',
                ]);

                $entryType->setFieldLayout($fieldLayout);
                if ($entriesService->saveEntryType($entryType)) {
                    echo "    - Added field layout to Photo\n";
                }
                break;
            }
        }
    }

    /**
     * Add photography relationships to Travels and Thoughts
     */
    private function addPhotographyRelationshipsToSections(): void
    {
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        $relatedPhotographyField = $fieldsService->getFieldByHandle('relatedPhotographyAlbums');
        if (!$relatedPhotographyField) {
            return;
        }

        // Add to Travels
        $travelsSection = $entriesService->getSectionByHandle('travels');
        if ($travelsSection) {
            $travelEntryTypes = $entriesService->getEntryTypesBySectionId($travelsSection->id);
            foreach ($travelEntryTypes as $entryType) {
                $layout = $entryType->getFieldLayout();
                if ($layout) {
                    $tabs = $layout->getTabs();
                    if (!empty($tabs)) {
                        // Find or create Related Content tab
                        $relTab = null;
                        foreach ($tabs as $tab) {
                            if ($tab->name === 'Related Content') {
                                $relTab = $tab;
                                break;
                            }
                        }

                        if ($relTab) {
                            // Add to existing tab
                            $elements = $relTab->getElements();
                            $elements[] = new CustomField($relatedPhotographyField);
                            $relTab->setElements($elements);
                        } else {
                            // Create new tab
                            $relTab = new FieldLayoutTab([
                                'layout' => $layout,
                                'name' => 'Related Content',
                                'sortOrder' => count($tabs) + 1,
                            ]);
                            $relTab->setElements([new CustomField($relatedPhotographyField)]);
                            $tabs[] = $relTab;
                        }

                        $layout->setTabs($tabs);
                        $entryType->setFieldLayout($layout);
                        $entriesService->saveEntryType($entryType);
                    }
                }
            }
        }

        // Add to Thoughts
        $thoughtsSection = $entriesService->getSectionByHandle('thoughts');
        if ($thoughtsSection) {
            $thoughtsEntryTypes = $entriesService->getEntryTypesBySectionId($thoughtsSection->id);
            foreach ($thoughtsEntryTypes as $entryType) {
                $layout = $entryType->getFieldLayout();
                if ($layout) {
                    $tabs = $layout->getTabs();
                    if (!empty($tabs)) {
                        // Find or create Related Content tab
                        $relTab = null;
                        foreach ($tabs as $tab) {
                            if ($tab->name === 'Related Content') {
                                $relTab = $tab;
                                break;
                            }
                        }

                        if ($relTab) {
                            // Add to existing tab
                            $elements = $relTab->getElements();
                            $elements[] = new CustomField($relatedPhotographyField);
                            $relTab->setElements($elements);
                        } else {
                            // Create new tab
                            $relTab = new FieldLayoutTab([
                                'layout' => $layout,
                                'name' => 'Related Content',
                                'sortOrder' => count($tabs) + 1,
                            ]);
                            $relTab->setElements([new CustomField($relatedPhotographyField)]);
                            $tabs[] = $relTab;
                        }

                        $layout->setTabs($tabs);
                        $entryType->setFieldLayout($layout);
                        $entriesService->saveEntryType($entryType);
                    }
                }
            }
        }

        echo "  ✓ Added Photography relationships to Travels and Thoughts\n";
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
        echo "m251018_103821_restructure_photography cannot be reverted.\n";
        return false;
    }
}
