<?php

namespace App\Services;

readonly class FilterResult
{
    public function __construct(
        public array $clean,
        public array $junk,
        public array $flagged,
    ) {}
}

class SoftwareFilterService
{
    public const PRIORITY_KEYWORDS = [
        'Epic Games',
        'Steam',
        'Ubisoft',
        'Crack',
        'Patch',
        'Keygen',
        'Activator',
        'Torrent',
        'uTorrent',
        'BitTorrent',
        'Daemon Tools',
        'Pirate',
    ];

    public const JUNK_KEYWORDS = [
        'Redistributable',
        'Runtime',
        'Framework',
        'Library',
        'SDK',
        'API',
        'DirectX',
        'Vulkan',
        'OpenGL',
        'Prerequisites',
        'Driver',
        'Chipset',
        'PhysX',
        'GeForce',
        'Radeon',
        'Intel(R)',
        'Realtek',
        'BIOS',
        'Firmware',
        'Update',
        'KB',
        'Patch',
        'Service Pack',
        'Language Pack',
        'Feature Pack',
        'Support',
        'Bootstrap',
        'Test Suite',
        'Documentation',
        'Help',
        'Manual',
        'Setup',
        'Installer',
        'Launcher',
        'Helper',
        'Agent',
        'Updater',
        'Assistant',
        'Wizard',
        'Tool',
        'Bridge',
        'Connector',
        'Plugin',
        'Extension',
        'Add-in',
        'Addon',
        'WebResource',
        'Click-to-Run',
        'Extensibility',
        'Localization',
        'Licensing Component',
        'AppHost',
        'Host FX',
        'vs_',
        'Minshell',
        'Redist',
        'Client Profile',
        'Targeting Pack',
        'Visual C++',
        '.NET Framework',
    ];

    /**
     * Filter the software list into clean, junk, and flagged categories.
     * Logic: Priority keywords bypass junk filter and are flagged.
     */
    public function filter(array $softwareList): FilterResult
    {
        $clean = [];
        $junk = [];
        $flagged = [];

        foreach ($softwareList as $soft) {
            $name = $soft['name'] ?? null;
            if (empty($name)) {
                continue;
            }

            $isPriority = false;
            foreach (self::PRIORITY_KEYWORDS as $pk) {
                if (stripos($name, $pk) !== false) {
                    $isPriority = true;
                    break;
                }
            }

            if ($isPriority) {
                $flagged[] = $soft;
                $clean[] = $soft;
                continue;
            }

            $isJunk = false;
            foreach (self::JUNK_KEYWORDS as $jk) {
                if (stripos($name, $jk) !== false) {
                    $isJunk = true;
                    break;
                }
            }

            if ($isJunk) {
                $junk[] = $soft;
            } else {
                $clean[] = $soft;
            }
        }

        return new FilterResult($clean, $junk, $flagged);
    }
}
