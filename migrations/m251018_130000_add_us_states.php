<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Category;

/**
 * m251018_130000_add_us_states migration.
 */
class m251018_130000_add_us_states extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        echo "\n=== Adding US States ===\n\n";

        $categoriesService = Craft::$app->getCategories();

        // Get the Locations category group
        $locationsGroup = $categoriesService->getGroupByHandle('locations');
        if (!$locationsGroup) {
            echo "  ✗ Locations category group not found\n";
            return false;
        }

        // Find the USA category
        $usa = Category::find()
            ->group('locations')
            ->title('USA')
            ->one();

        if (!$usa) {
            echo "  ✗ USA category not found. Please create it first.\n";
            return false;
        }

        echo "  ✓ Found USA category (ID: {$usa->id})\n\n";

        // All 50 US states (alphabetically)
        $states = [
            'Alabama',
            'Alaska',
            'Arizona',
            'Arkansas',
            'California',
            'Colorado',
            'Connecticut',
            'Delaware',
            'Florida',
            'Georgia',
            'Hawaii',
            'Idaho',
            'Illinois',
            'Indiana',
            'Iowa',
            'Kansas',
            'Kentucky',
            'Louisiana',
            'Maine',
            'Maryland',
            'Massachusetts', // Already exists, will skip
            'Michigan',
            'Minnesota',
            'Mississippi',
            'Missouri',
            'Montana',
            'Nebraska',
            'Nevada',
            'New Hampshire',
            'New Jersey',
            'New Mexico',
            'New York',
            'North Carolina',
            'North Dakota',
            'Ohio',
            'Oklahoma',
            'Oregon',
            'Pennsylvania',
            'Rhode Island',
            'South Carolina',
            'South Dakota',
            'Tennessee',
            'Texas',
            'Utah',
            'Vermont',
            'Virginia',
            'Washington',
            'West Virginia',
            'Wisconsin',
            'Wyoming',
        ];

        $created = 0;
        $skipped = 0;

        foreach ($states as $stateName) {
            // Check if state already exists
            $existing = Category::find()
                ->group('locations')
                ->title($stateName)
                ->one();

            if ($existing) {
                echo "  ⊘ Skipped: {$stateName} (already exists)\n";
                $skipped++;
                continue;
            }

            // Create new state category
            $state = new Category([
                'groupId' => $locationsGroup->id,
                'title' => $stateName,
            ]);

            // Set USA as parent
            $state->setParent($usa);

            // Save the category
            if (Craft::$app->getElements()->saveElement($state)) {
                echo "  ✓ Created: {$stateName}\n";
                $created++;
            } else {
                echo "  ✗ Failed to create: {$stateName}\n";
                echo "    Errors: " . json_encode($state->getErrors()) . "\n";
            }
        }

        echo "\n📊 Summary:\n";
        echo "  Created: {$created} states\n";
        echo "  Skipped: {$skipped} states (already existed)\n";
        echo "\n✅ Done!\n\n";

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m251018_130000_add_us_states cannot be reverted.\n";
        return false;
    }
}
