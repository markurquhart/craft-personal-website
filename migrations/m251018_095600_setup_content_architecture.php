<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\fieldlayoutelements\CustomField;
use craft\fields\PlainText;
use craft\fields\Date;
use craft\fields\Dropdown;
use craft\fields\Entries;
use craft\fields\Assets;
use craft\fields\Categories as CategoriesField;
use craft\elements\Entry;
use craft\enums\PropagationMethod;

/**
 * m251018_095600_setup_content_architecture migration.
 */
class m251018_095600_setup_content_architecture extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        echo "\n=== Setting up Personal Website Content Architecture ===\n\n";

        $this->createCategoryGroups();
        $this->createFields();
        $this->createSingleSections();
        $this->createChannelSections();

        echo "\n✅ Content architecture created successfully!\n\n";

        return true;
    }

    /**
     * Create category groups
     */
    private function createCategoryGroups(): void
    {
        echo "📁 Creating category groups...\n";

        $categoriesService = Craft::$app->getCategories();
        $sitesService = Craft::$app->getSites();
        $primarySite = $sitesService->getPrimarySite();

        // Creative Categories
        $creativeCategories = new CategoryGroup([
            'name' => 'Creative Categories',
            'handle' => 'creativeCategories',
        ]);

        $creativeSiteSettings = new CategoryGroup_SiteSettings([
            'siteId' => $primarySite->id,
            'hasUrls' => true,
            'uriFormat' => 'creative/category/{slug}',
            'template' => 'creative/_category',
        ]);

        $creativeCategories->setSiteSettings([$creativeSiteSettings]);

        if ($categoriesService->saveGroup($creativeCategories)) {
            echo "  ✓ Creative Categories\n";
        }

        // Blog Categories
        $blogCategories = new CategoryGroup([
            'name' => 'Blog Categories',
            'handle' => 'blogCategories',
        ]);

        $blogSiteSettings = new CategoryGroup_SiteSettings([
            'siteId' => $primarySite->id,
            'hasUrls' => true,
            'uriFormat' => 'thoughts/category/{slug}',
            'template' => 'thoughts/_category',
        ]);

        $blogCategories->setSiteSettings([$blogSiteSettings]);

        if ($categoriesService->saveGroup($blogCategories)) {
            echo "  ✓ Blog Categories\n";
        }
    }

    /**
     * Create custom fields
     */
    private function createFields(): void
    {
        echo "\n🔧 Creating custom fields...\n";

        $fieldsService = Craft::$app->getFields();

        // Photography Metadata Fields
        $photographyFields = [
            'dateTaken' => new Date(['name' => 'Date Taken', 'handle' => 'dateTaken']),
            'photoLocation' => new PlainText(['name' => 'Location', 'handle' => 'photoLocation']),
            'camera' => new PlainText(['name' => 'Camera', 'handle' => 'camera']),
            'lens' => new PlainText(['name' => 'Lens', 'handle' => 'lens']),
            'iso' => new PlainText(['name' => 'ISO', 'handle' => 'iso']),
            'aperture' => new PlainText(['name' => 'Aperture', 'handle' => 'aperture']),
            'shutterSpeed' => new PlainText(['name' => 'Shutter Speed', 'handle' => 'shutterSpeed']),
        ];

        // Project Information Fields
        $projectFields = [
            'client' => new PlainText(['name' => 'Client', 'handle' => 'client']),
            'projectDate' => new Date(['name' => 'Project Date', 'handle' => 'projectDate']),
            'technologies' => new PlainText([
                'name' => 'Technologies',
                'handle' => 'technologies',
                'multiline' => true,
                'initialRows' => 4,
            ]),
            'projectUrl' => new PlainText(['name' => 'Project URL', 'handle' => 'projectUrl']),
        ];

        // Travel Information Fields
        $travelFields = [
            'destination' => new PlainText(['name' => 'Destination', 'handle' => 'destination']),
            'startDate' => new Date(['name' => 'Start Date', 'handle' => 'startDate']),
            'endDate' => new Date(['name' => 'End Date', 'handle' => 'endDate']),
            'travelType' => new Dropdown([
                'name' => 'Travel Type',
                'handle' => 'travelType',
                'options' => [
                    ['label' => 'Family Vacation', 'value' => 'family', 'default' => false],
                    ['label' => 'Personal Travel', 'value' => 'personal', 'default' => false],
                    ['label' => 'Work Travel', 'value' => 'work', 'default' => false],
                ],
            ]),
        ];

        // Common Fields
        $commonFields = [
            'featuredImage' => new Assets([
                'name' => 'Featured Image',
                'handle' => 'featuredImage',
                'restrictFiles' => true,
                'allowedKinds' => ['image'],
                'limit' => 1,
            ]),
            'gallery' => new Assets([
                'name' => 'Gallery',
                'handle' => 'gallery',
                'restrictFiles' => true,
                'allowedKinds' => ['image'],
            ]),
            'description' => new PlainText([
                'name' => 'Description',
                'handle' => 'description',
                'multiline' => true,
                'initialRows' => 6,
            ]),
        ];

        // Creative Categories field
        $creativeCategories = Craft::$app->getCategories()->getGroupByHandle('creativeCategories');
        $blogCategories = Craft::$app->getCategories()->getGroupByHandle('blogCategories');

        $categoryFields = [
            'creativeCategories' => new CategoriesField([
                'name' => 'Creative Categories',
                'handle' => 'creativeCategories',
                'sources' => ['group:' . $creativeCategories->uid],
            ]),
            'blogCategories' => new CategoriesField([
                'name' => 'Blog Categories',
                'handle' => 'blogCategories',
                'sources' => ['group:' . $blogCategories->uid],
            ]),
        ];

        // Save all fields
        $allFields = array_merge(
            $photographyFields,
            $projectFields,
            $travelFields,
            $commonFields,
            $categoryFields
        );

        foreach ($allFields as $handle => $field) {
            if ($fieldsService->saveField($field)) {
                echo "  ✓ {$field->name}\n";
            } else {
                echo "  ✗ Failed to create: {$field->name}\n";
            }
        }

        // Store field handles for later use
        $this->fieldHandles = array_keys($allFields);
    }

    private $fieldHandles = [];

    /**
     * Create single sections (static pages)
     */
    private function createSingleSections(): void
    {
        echo "\n📄 Creating single sections...\n";

        $entriesService = Craft::$app->getEntries();
        $sitesService = Craft::$app->getSites();
        $primarySite = $sitesService->getPrimarySite();

        $singles = [
            ['name' => 'Home', 'handle' => 'home', 'uri' => '__home__'],
            ['name' => 'About', 'handle' => 'about', 'uri' => 'about'],
            ['name' => 'Contact', 'handle' => 'contact', 'uri' => 'contact'],
        ];

        foreach ($singles as $singleConfig) {
            // Create and SAVE the entry type FIRST
            $entryType = new EntryType([
                'name' => $singleConfig['name'],
                'handle' => $singleConfig['handle'],
                'uid' => StringHelper::UUID(),
            ]);

            if (!$entriesService->saveEntryType($entryType)) {
                echo "  ✗ Failed to create entry type for {$singleConfig['name']}\n";
                continue;
            }

            // Now create the section
            $section = new Section([
                'name' => $singleConfig['name'],
                'handle' => $singleConfig['handle'],
                'type' => Section::TYPE_SINGLE,
                'propagationMethod' => PropagationMethod::All,
            ]);

            $siteSettings = new Section_SiteSettings([
                'siteId' => $primarySite->id,
                'hasUrls' => true,
                'uriFormat' => $singleConfig['uri'],
                'template' => $singleConfig['handle'] . '/index',
                'enabledByDefault' => true,
            ]);

            $section->setSiteSettings([$siteSettings]);

            // Assign the SAVED entry type to the section
            $section->setEntryTypes([$entryType]);

            if ($entriesService->saveSection($section)) {
                echo "  ✓ {$singleConfig['name']}\n";
            } else {
                echo "  ✗ Failed to create {$singleConfig['name']}: " . json_encode($section->getErrors()) . "\n";
            }
        }
    }

    /**
     * Create channel sections with entry types and field layouts
     */
    private function createChannelSections(): void
    {
        echo "\n📚 Creating channel sections...\n";

        // Creative Projects Channel
        $this->createCreativeProjectsSection();

        // Thoughts (Blog) Channel
        $this->createThoughtsSection();

        // Travels Channel
        $this->createTravelsSection();

        // Now create relationship fields (need section UIDs)
        $this->createRelationshipFields();
    }

    /**
     * Create Creative Projects section with entry types
     */
    private function createCreativeProjectsSection(): void
    {
        $entriesService = Craft::$app->getEntries();
        $fieldsService = Craft::$app->getFields();
        $sitesService = Craft::$app->getSites();
        $primarySite = $sitesService->getPrimarySite();

        // Create and SAVE entry types FIRST
        $entryTypes = [
            new EntryType(['name' => 'Photography Project', 'handle' => 'photographyProject', 'uid' => StringHelper::UUID()]),
            new EntryType(['name' => 'Website Project', 'handle' => 'websiteProject', 'uid' => StringHelper::UUID()]),
            new EntryType(['name' => 'Graphic Design Project', 'handle' => 'graphicDesignProject', 'uid' => StringHelper::UUID()]),
        ];

        foreach ($entryTypes as $entryType) {
            if (!$entriesService->saveEntryType($entryType)) {
                echo "  ✗ Failed to create entry type: {$entryType->name}\n";
                return;
            }
        }

        // Now create the section
        $section = new Section([
            'name' => 'Creative Projects',
            'handle' => 'creativeProjects',
            'type' => Section::TYPE_CHANNEL,
            'propagationMethod' => PropagationMethod::All,
        ]);

        $siteSettings = new Section_SiteSettings([
            'siteId' => $primarySite->id,
            'hasUrls' => true,
            'uriFormat' => 'creative/{slug}',
            'template' => 'creative/_entry',
            'enabledByDefault' => true,
        ]);

        $section->setSiteSettings([$siteSettings]);

        // Assign the SAVED entry types
        $section->setEntryTypes($entryTypes);

        if ($entriesService->saveSection($section)) {
            echo "  ✓ Creative Projects\n";

            // Now add field layouts to the saved entry types
            $this->addFieldLayoutsToCreativeProjects($section);
        } else {
            echo "  ✗ Failed to create Creative Projects: " . json_encode($section->getErrors()) . "\n";
        }
    }

    /**
     * Create Thoughts (Blog) section
     */
    private function createThoughtsSection(): void
    {
        $entriesService = Craft::$app->getEntries();
        $sitesService = Craft::$app->getSites();
        $primarySite = $sitesService->getPrimarySite();

        // Create and SAVE entry type FIRST
        $entryType = new EntryType(['name' => 'Blog Post', 'handle' => 'blogPost', 'uid' => StringHelper::UUID()]);

        if (!$entriesService->saveEntryType($entryType)) {
            echo "  ✗ Failed to create entry type: Blog Post\n";
            return;
        }

        // Now create the section
        $section = new Section([
            'name' => 'Thoughts',
            'handle' => 'thoughts',
            'type' => Section::TYPE_CHANNEL,
            'propagationMethod' => PropagationMethod::All,
        ]);

        $siteSettings = new Section_SiteSettings([
            'siteId' => $primarySite->id,
            'hasUrls' => true,
            'uriFormat' => 'thoughts/{slug}',
            'template' => 'thoughts/_entry',
            'enabledByDefault' => true,
        ]);

        $section->setSiteSettings([$siteSettings]);

        // Assign the SAVED entry type
        $section->setEntryTypes([$entryType]);

        if ($entriesService->saveSection($section)) {
            echo "  ✓ Thoughts (Blog)\n";

            // Add field layout
            $this->addFieldLayoutsToThoughts($section);
        } else {
            echo "  ✗ Failed to create Thoughts: " . json_encode($section->getErrors()) . "\n";
        }
    }

    /**
     * Create Travels section
     */
    private function createTravelsSection(): void
    {
        $entriesService = Craft::$app->getEntries();
        $sitesService = Craft::$app->getSites();
        $primarySite = $sitesService->getPrimarySite();

        // Create and SAVE entry type FIRST
        $entryType = new EntryType(['name' => 'Travel Entry', 'handle' => 'travelEntry', 'uid' => StringHelper::UUID()]);

        if (!$entriesService->saveEntryType($entryType)) {
            echo "  ✗ Failed to create entry type: Travel Entry\n";
            return;
        }

        // Now create the section
        $section = new Section([
            'name' => 'Travels',
            'handle' => 'travels',
            'type' => Section::TYPE_CHANNEL,
            'propagationMethod' => PropagationMethod::All,
        ]);

        $siteSettings = new Section_SiteSettings([
            'siteId' => $primarySite->id,
            'hasUrls' => true,
            'uriFormat' => 'travels/{slug}',
            'template' => 'travels/_entry',
            'enabledByDefault' => true,
        ]);

        $section->setSiteSettings([$siteSettings]);

        // Assign the SAVED entry type
        $section->setEntryTypes([$entryType]);

        if ($entriesService->saveSection($section)) {
            echo "  ✓ Travels\n";

            // Add field layout
            $this->addFieldLayoutsToTravels($section);
        } else {
            echo "  ✗ Failed to create Travels: " . json_encode($section->getErrors()) . "\n";
        }
    }

    /**
     * Add field layouts to Creative Projects entry types
     */
    private function addFieldLayoutsToCreativeProjects(Section $section): void
    {
        $entriesService = Craft::$app->getEntries();
        $entryTypes = $entriesService->getEntryTypesBySectionId($section->id);

        foreach ($entryTypes as $entryType) {
            $fieldLayout = null;

            switch ($entryType->handle) {
                case 'photographyProject':
                    // Photography projects include photo metadata
                    $fieldLayout = $this->createFieldLayout([
                        'featuredImage',
                        'gallery',
                        'description',
                        'dateTaken',
                        'photoLocation',
                        'camera',
                        'lens',
                        'iso',
                        'aperture',
                        'shutterSpeed',
                        'projectDate',
                        'creativeCategories',
                    ]);
                    break;

                case 'websiteProject':
                    // Website projects include technical details
                    $fieldLayout = $this->createFieldLayout([
                        'featuredImage',
                        'gallery',
                        'description',
                        'client',
                        'projectDate',
                        'technologies',
                        'projectUrl',
                        'creativeCategories',
                    ]);
                    break;

                case 'graphicDesignProject':
                    // Design projects
                    $fieldLayout = $this->createFieldLayout([
                        'featuredImage',
                        'gallery',
                        'description',
                        'client',
                        'projectDate',
                        'technologies',
                        'creativeCategories',
                    ]);
                    break;
            }

            if ($fieldLayout) {
                $entryType->setFieldLayout($fieldLayout);
                if ($entriesService->saveEntryType($entryType)) {
                    echo "    - Added field layout to {$entryType->name}\n";
                }
            }
        }
    }

    /**
     * Add field layouts to Thoughts entry types
     */
    private function addFieldLayoutsToThoughts(Section $section): void
    {
        $entriesService = Craft::$app->getEntries();
        $entryTypes = $entriesService->getEntryTypesBySectionId($section->id);

        foreach ($entryTypes as $entryType) {
            $fieldLayout = $this->createFieldLayout([
                'featuredImage',
                'description',
                'blogCategories',
            ]);

            $entryType->setFieldLayout($fieldLayout);
            if ($entriesService->saveEntryType($entryType)) {
                echo "    - Added field layout to {$entryType->name}\n";
            }
        }
    }

    /**
     * Add field layouts to Travels entry types
     */
    private function addFieldLayoutsToTravels(Section $section): void
    {
        $entriesService = Craft::$app->getEntries();
        $entryTypes = $entriesService->getEntryTypesBySectionId($section->id);

        foreach ($entryTypes as $entryType) {
            $fieldLayout = $this->createFieldLayout([
                'featuredImage',
                'gallery',
                'description',
                'destination',
                'startDate',
                'endDate',
                'travelType',
            ]);

            $entryType->setFieldLayout($fieldLayout);
            if ($entriesService->saveEntryType($entryType)) {
                echo "    - Added field layout to {$entryType->name}\n";
            }
        }
    }

    /**
     * Create entry types with field layouts
     */
    private function createEntryTypesWithLayouts(Section $section, array $entryTypesConfig): void
    {
        $entriesService = Craft::$app->getEntries();
        $fieldsService = Craft::$app->getFields();

        // Get the default entry type that was created with the section
        $existingEntryTypes = $entriesService->getEntryTypesBySectionId($section->id);
        $defaultEntryType = $existingEntryTypes[0];

        // Update the first entry type
        $firstConfig = array_shift($entryTypesConfig);
        $defaultEntryType->name = $firstConfig['name'];
        $defaultEntryType->handle = $firstConfig['handle'];

        // Create field layout for first entry type
        if (isset($firstConfig['fields'])) {
            $fieldLayout = $this->createFieldLayout($firstConfig['fields']);
            $defaultEntryType->setFieldLayout($fieldLayout);
        }

        if ($entriesService->saveEntryType($defaultEntryType)) {
            echo "    - {$firstConfig['name']}\n";
        }

        // Create additional entry types
        foreach ($entryTypesConfig as $config) {
            $entryType = new EntryType([
                'sectionId' => $section->id,
                'name' => $config['name'],
                'handle' => $config['handle'],
            ]);

            if (isset($config['fields'])) {
                $fieldLayout = $this->createFieldLayout($config['fields']);
                $entryType->setFieldLayout($fieldLayout);
            }

            if ($entriesService->saveEntryType($entryType)) {
                echo "    - {$config['name']}\n";
            }
        }
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
     * Create relationship fields between sections
     */
    private function createRelationshipFields(): void
    {
        echo "\n🔗 Creating relationship fields...\n";

        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        $creativeSection = $entriesService->getSectionByHandle('creativeProjects');
        $thoughtsSection = $entriesService->getSectionByHandle('thoughts');
        $travelsSection = $entriesService->getSectionByHandle('travels');

        // Related Creative Projects field
        $relatedCreativeField = new Entries([
            'name' => 'Related Creative Projects',
            'handle' => 'relatedCreativeProjects',
            'sources' => ['section:' . $creativeSection->uid],
        ]);

        if ($fieldsService->saveField($relatedCreativeField)) {
            echo "  ✓ Related Creative Projects\n";
        }

        // Related Blog Posts field
        $relatedThoughtsField = new Entries([
            'name' => 'Related Blog Posts',
            'handle' => 'relatedBlogPosts',
            'sources' => ['section:' . $thoughtsSection->uid],
        ]);

        if ($fieldsService->saveField($relatedThoughtsField)) {
            echo "  ✓ Related Blog Posts\n";
        }

        // Related Travels field
        $relatedTravelsField = new Entries([
            'name' => 'Related Travels',
            'handle' => 'relatedTravels',
            'sources' => ['section:' . $travelsSection->uid],
        ]);

        if ($fieldsService->saveField($relatedTravelsField)) {
            echo "  ✓ Related Travels\n";
        }

        // Now add these fields to the appropriate entry types
        $this->addRelationshipFieldsToLayouts();
    }

    /**
     * Add relationship fields to entry type layouts
     */
    private function addRelationshipFieldsToLayouts(): void
    {
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        // Add relationship fields to Travels entries
        $travelsSection = $entriesService->getSectionByHandle('travels');
        $travelEntryTypes = $entriesService->getEntryTypesBySectionId($travelsSection->id);

        foreach ($travelEntryTypes as $entryType) {
            $layout = $entryType->getFieldLayout();
            if ($layout) {
                $tabs = $layout->getTabs();
                if (!empty($tabs)) {
                    $relTab = new FieldLayoutTab([
                        'layout' => $layout,
                        'name' => 'Related Content',
                        'sortOrder' => 2,
                    ]);

                    $relElements = [
                        new CustomField($fieldsService->getFieldByHandle('relatedCreativeProjects')),
                        new CustomField($fieldsService->getFieldByHandle('relatedBlogPosts')),
                    ];

                    $relTab->setElements($relElements);
                    $tabs[] = $relTab;
                    $layout->setTabs($tabs);

                    $entryType->setFieldLayout($layout);
                    $entriesService->saveEntryType($entryType);
                }
            }
        }

        // Add to Thoughts entries
        $thoughtsSection = $entriesService->getSectionByHandle('thoughts');
        $thoughtsEntryTypes = $entriesService->getEntryTypesBySectionId($thoughtsSection->id);

        foreach ($thoughtsEntryTypes as $entryType) {
            $layout = $entryType->getFieldLayout();
            if ($layout) {
                $tabs = $layout->getTabs();
                if (!empty($tabs)) {
                    $relTab = new FieldLayoutTab([
                        'layout' => $layout,
                        'name' => 'Related Content',
                        'sortOrder' => 2,
                    ]);

                    $relElements = [
                        new CustomField($fieldsService->getFieldByHandle('relatedCreativeProjects')),
                        new CustomField($fieldsService->getFieldByHandle('relatedTravels')),
                    ];

                    $relTab->setElements($relElements);
                    $tabs[] = $relTab;
                    $layout->setTabs($tabs);

                    $entryType->setFieldLayout($layout);
                    $entriesService->saveEntryType($entryType);
                }
            }
        }

        echo "  ✓ Added relationship fields to entry types\n";
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m251018_095600_setup_content_architecture cannot be reverted.\n";
        return false;
    }
}
