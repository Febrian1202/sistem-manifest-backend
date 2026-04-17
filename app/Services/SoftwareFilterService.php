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
    /**
     * Keywords that indicate potentially unauthorized/pirated software.
     * These bypass the junk filter and are always recorded + flagged.
     */
    public const PRIORITY_KEYWORDS = [
        'Crack',
        'Keygen',
        'Activator',
        'Pirate',
        'ElAmigos',
        'FitGirl',
        'CODEX',
        'SKIDROW',
        'PLAZA',
        'EMPRESS',
        'Repack',
        'KMSPico',
        'KMSAuto',
        'KMS Activator',
        'Loader',
        'Cheat Engine',
    ];

    /**
     * Keywords that indicate system components, drivers, libraries, or non-user-facing software.
     * These are filtered out and not stored in the database.
     */
    public const JUNK_KEYWORDS = [
        // ---- Runtime / Framework / Libraries ----
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
        'Visual C++',
        '.NET Framework',
        '.NET Host',
        '.NET Templates',
        '.NET Runtime',
        '.NET SDK',
        'Targeting Pack',
        'Client Profile',
        'AppHost',
        'Host FX',
        'Workload',
        'Manifest (x64)',
        'Manifest (x86)',

        // ---- Drivers / Hardware / Firmware ----
        'Driver',
        'Chipset',
        'PhysX',
        'GeForce',
        'Radeon',
        'Intel(R)',
        'Realtek',
        'BIOS',
        'Firmware',
        'Branding64',
        'DVR64',
        'WVR64',
        'OSD',

        // ---- AMD specific components ----
        'AMD Install Manager',
        'AMD Privacy View',
        'AMD Settings',
        'AMD Software',
        'AdvancedMicroDevicesInc',
        'RSXCM',

        // ---- Updates / Patches / Service Packs ----
        'Update',
        'Patch',
        'Service Pack',
        'Language Pack',
        'Feature Pack',
        'Hotfix',
        'Security Update',

        // ---- System utilities / non-user-facing ----
        'Setup',
        'Installer',
        'Helper',
        'Updater',
        'Assistant',
        'Wizard',
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
        'vs_',
        'Minshell',
        'Redist',
        'Bootstrap',
        'Test Suite',
        'Documentation',
        'Help',
        'Manual',

        // ---- DiagnosticsHub / Telemetry ----
        'DiagnosticsHub',
        'Diagnostics',
        'Telemetry',
        'CollectionService',

        // ---- Misc system components ----
        'Online Services',
        'GamingServices',
        'GameInput',
        'Winget.Source',
        'StorePurchaseApp',
        'WebExperience',
        'CrossDevice',
        'StartExperiencesApp',
        'Client.CBS',
        'LanguageExperiencePack',
        'ApplicationCompatibility',
        'MixedReality.Portal',

        // ---- Python sub-components ----
        'Core Interpreter',
        'Development Libraries',
        'Executables (64-bit)',
        'Executables (32-bit)',
        'Add to Path',
        'Pip Bootstrap',
        'Tcl/Tk',
        'Test Framework',

        // ---- Epson / Printer components ----
        'USB Display',
        'Epson USB',

        // ---- License server components ----
        'License Server',
    ];

    /**
     * Regex patterns that indicate junk entries.
     * Checked separately from keyword matching.
     */
    public const JUNK_PATTERNS = [
        // Windows Store App IDs (e.g. "Microsoft.Paint", "SpotifyAB.SpotifyMusic", "5319275A.WhatsAppDesktop")
        // Format: Publisher.AppName or HexID.AppName (no spaces, PascalCase)
        '/^[A-Za-z0-9]+\.[A-Za-z][A-Za-z0-9\.]+$/',

        // Hex-prefixed Windows Store apps (e.g. "40459File-New-Project.EarTrumpet", "5259FreeSoftwareApps.AudioConverter-Free")
        '/^\d+[A-Za-z].*\..+$/',

        // Python sub-component entries (e.g. "Python 3.12.6 Core Interpreter (64-bit)")
        '/^Python \d+\.\d+\.\d+ (Core Interpreter|Development Libraries|Executables|Add to Path|Standard Library|Documentation|Utility Scripts|Tcl|Test|pip)/',

        // Microsoft .NET sub-entries
        '/^Microsoft \.NET \d+\.\d+ (Templates|Host|Runtime|SDK)/',

        // KB updates (e.g. "KB1234567")
        '/^KB\d+/',
    ];

    /**
     * Filter the software list into clean, junk, and flagged categories.
     *
     * Logic:
     * 1. Priority keywords bypass junk filter and are always flagged.
     * 2. Junk keywords and patterns filter out system components, drivers, etc.
     * 3. Everything else is "clean" (legitimate user-facing software).
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

            // --- Step 1: Check Priority (always record, always flag) ---
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

            // --- Step 2: Check Junk Keywords ---
            $isJunk = false;
            foreach (self::JUNK_KEYWORDS as $jk) {
                if (stripos($name, $jk) !== false) {
                    $isJunk = true;
                    break;
                }
            }

            // --- Step 3: Check Junk Regex Patterns ---
            if (!$isJunk) {
                foreach (self::JUNK_PATTERNS as $pattern) {
                    if (preg_match($pattern, $name)) {
                        $isJunk = true;
                        break;
                    }
                }
            }

            // --- Step 4: Classify ---
            if ($isJunk) {
                $junk[] = $soft;
            } else {
                $clean[] = $soft;
            }
        }

        return new FilterResult($clean, $junk, $flagged);
    }
}

