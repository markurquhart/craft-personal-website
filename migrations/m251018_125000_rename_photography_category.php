<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;

/**
 * m251018_125000_rename_photography_category migration.
 */
class m251018_125000_rename_photography_category extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        echo "\n=== Renaming Photography Category to Genre ===\n\n";

        $entriesService = Craft::$app->getEntries();
        $photographySection = $entriesService->getSectionByHandle('photography');

        if ($photographySection) {
            $entryTypes = $entriesService->getEntryTypesBySectionId($photographySection->id);

            foreach ($entryTypes as $entryType) {
                if ($entryType->handle === 'category') {
                    echo "📝 Renaming 'Category' to 'Genre'...\n";

                    $entryType->name = 'Genre';
                    $entryType->handle = 'genre';

                    if ($entriesService->saveEntryType($entryType)) {
                        echo "  ✓ Photography entry type renamed to 'Genre'\n";
                    } else {
                        echo "  ✗ Failed to rename: " . json_encode($entryType->getErrors()) . "\n";
                        return false;
                    }
                    break;
                }
            }
        }

        echo "\n✅ Photography Category renamed to Genre!\n\n";
        echo "Now your photography hierarchy is:\n";
        echo "  Genre (Sports, City, Wildlife, Outdoors)\n";
        echo "    → Album (wedding, vacation, etc.)\n";
        echo "      → Photo (individual images)\n\n";
        echo "And Locations are separate:\n";
        echo "  Country (USA, Netherlands)\n";
        echo "    → State (Massachusetts, North Holland)\n";
        echo "      → City (Boston, Amsterdam)\n\n";

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m251018_125000_rename_photography_category cannot be reverted.\n";
        return false;
    }
}
