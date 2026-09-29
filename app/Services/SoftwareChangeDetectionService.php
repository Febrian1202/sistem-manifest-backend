<?php

namespace App\Services;

use App\Models\ComplianceSnapshot;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SoftwareChangeDetectionService
{
    /**
     * Compare software results between two scan sessions.
     *
     * @return array{
     *     current_session: ScanSession,
     *     previous_session: ?ScanSession,
     *     added: array<int, array<string, mixed>>,
     *     removed: array<int, array<string, mixed>>,
     *     changed: array<int, array<string, mixed>>,
     *     returned: array<int, array<string, mixed>>,
     *     summary: array{added_count: int, removed_count: int, changed_count: int, returned_count: int, total_changes: int}
     * }
     */
    public function compareSessions(ScanSession $currentSession, ?ScanSession $previousSession = null): array
    {
        if ($previousSession === null) {
            $previousSession = ScanSession::where('computer_id', $currentSession->computer_id)
                ->where('id', '<', $currentSession->id)
                ->where('status', 'completed')
                ->latest('started_at')
                ->latest('id')
                ->first();
        }

        $currentSoftware = ScanSoftwareResult::where('scan_session_id', $currentSession->id)->get();
        $previousSoftware = $previousSession
            ? ScanSoftwareResult::where('scan_session_id', $previousSession->id)->get()
            : collect();

        $currentMap = $currentSoftware->keyBy('raw_name');
        $previousMap = $previousSoftware->keyBy('raw_name');

        $added = [];
        $returned = [];
        $changed = [];
        $removed = [];

        // Check for added, returned, or changed software
        foreach ($currentMap as $rawName => $item) {
            if (! $previousMap->has($rawName)) {
                $wasPresentEarlier = false;
                if ($previousSession) {
                    $wasPresentEarlier = ScanSoftwareResult::whereHas('scanSession', function ($q) use ($currentSession, $previousSession) {
                        $q->where('computer_id', $currentSession->computer_id)
                            ->where('id', '<', $previousSession->id)
                            ->where('status', 'completed');
                    })->where('raw_name', $rawName)->exists();
                }

                if ($wasPresentEarlier) {
                    $returned[] = [
                        'raw_name' => $item->raw_name,
                        'version' => $item->version,
                        'vendor' => $item->vendor,
                        'install_date' => $item->install_date,
                        'type' => 'returned',
                        'current' => $item,
                    ];
                } else {
                    $added[] = [
                        'raw_name' => $item->raw_name,
                        'version' => $item->version,
                        'vendor' => $item->vendor,
                        'install_date' => $item->install_date,
                        'type' => 'added',
                        'current' => $item,
                    ];
                }
            } else {
                $prevItem = $previousMap->get($rawName);
                if ((string) $prevItem->version !== (string) $item->version) {
                    $changed[] = [
                        'raw_name' => $item->raw_name,
                        'old_version' => $prevItem->version,
                        'new_version' => $item->version,
                        'vendor' => $item->vendor,
                        'type' => 'version_changed',
                        'current' => $item,
                        'previous' => $prevItem,
                    ];
                }
            }
        }

        // Check for removed software
        foreach ($previousMap as $rawName => $prevItem) {
            if (! $currentMap->has($rawName)) {
                $removed[] = [
                    'raw_name' => $prevItem->raw_name,
                    'version' => $prevItem->version,
                    'vendor' => $prevItem->vendor,
                    'type' => 'removed',
                    'previous' => $prevItem,
                ];
            }
        }

        return [
            'current_session' => $currentSession,
            'previous_session' => $previousSession,
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
            'returned' => $returned,
            'summary' => [
                'added_count' => count($added),
                'removed_count' => count($removed),
                'changed_count' => count($changed),
                'returned_count' => count($returned),
                'total_changes' => count($added) + count($removed) + count($changed) + count($returned),
            ],
        ];
    }

    /**
     * Compare compliance snapshots between two sessions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function compareComplianceSnapshots(ScanSession $currentSession, ?ScanSession $previousSession = null): array
    {
        if ($previousSession === null) {
            $previousSession = ScanSession::where('computer_id', $currentSession->computer_id)
                ->where('id', '<', $currentSession->id)
                ->where('status', 'completed')
                ->latest('started_at')
                ->latest('id')
                ->first();
        }

        if (! $previousSession) {
            return [];
        }

        $currentSnapshots = ComplianceSnapshot::where('scan_session_id', $currentSession->id)->get()->keyBy('software_name');
        $prevSnapshots = ComplianceSnapshot::where('scan_session_id', $previousSession->id)->get()->keyBy('software_name');

        $statusChanges = [];

        foreach ($currentSnapshots as $name => $currentSnap) {
            if ($prevSnapshots->has($name)) {
                $prevSnap = $prevSnapshots->get($name);
                if ($prevSnap->status !== $currentSnap->status) {
                    $statusChanges[] = [
                        'software_name' => $currentSnap->software_name,
                        'software_version' => $currentSnap->software_version,
                        'old_status' => $prevSnap->status,
                        'new_status' => $currentSnap->status,
                        'old_keterangan' => $prevSnap->keterangan,
                        'new_keterangan' => $currentSnap->keterangan,
                        'current' => $currentSnap,
                        'previous' => $prevSnap,
                    ];
                }
            }
        }

        return $statusChanges;
    }

    /**
     * Retrieve a global feed/collection of detected changes across scan sessions.
     */
    public function getGlobalChanges(array $filters = []): Collection
    {
        $query = ScanSession::with(['computer.laboratory'])
            ->where('status', 'completed');

        if (! empty($filters['laboratory_id']) && $filters['laboratory_id'] !== 'All') {
            $query->whereHas('computer', function ($q) use ($filters) {
                $q->where('laboratory_id', $filters['laboratory_id']);
            });
        }

        if (! empty($filters['computer_id']) && $filters['computer_id'] !== 'All') {
            $query->where('computer_id', $filters['computer_id']);
        }

        if (! empty($filters['period_start'])) {
            $query->whereDate('started_at', '>=', Carbon::parse($filters['period_start']));
        }

        if (! empty($filters['period_end'])) {
            $query->whereDate('started_at', '<=', Carbon::parse($filters['period_end']));
        }

        $sessions = $query->orderBy('started_at')->orderBy('id')->get();
        $sessionsByComputer = $sessions->groupBy('computer_id');

        $allChanges = collect();

        foreach ($sessionsByComputer as $computerId => $computerSessions) {
            $previousSession = null;
            foreach ($computerSessions as $session) {
                if ($previousSession !== null) {
                    $diff = $this->compareSessions($session, $previousSession);

                    foreach ($diff['added'] as $item) {
                        $allChanges->push($this->formatChangeRecord($session, $item, 'added'));
                    }
                    foreach ($diff['removed'] as $item) {
                        $allChanges->push($this->formatChangeRecord($session, $item, 'removed'));
                    }
                    foreach ($diff['changed'] as $item) {
                        $allChanges->push($this->formatChangeRecord($session, $item, 'version_changed'));
                    }
                    foreach ($diff['returned'] as $item) {
                        $allChanges->push($this->formatChangeRecord($session, $item, 'returned'));
                    }
                } else {
                    // Check if there is an earlier completed session outside this query window
                    $earlierSession = ScanSession::where('computer_id', $computerId)
                        ->where('id', '<', $session->id)
                        ->where('status', 'completed')
                        ->latest('started_at')
                        ->first();

                    if ($earlierSession) {
                        $diff = $this->compareSessions($session, $earlierSession);

                        foreach ($diff['added'] as $item) {
                            $allChanges->push($this->formatChangeRecord($session, $item, 'added'));
                        }
                        foreach ($diff['removed'] as $item) {
                            $allChanges->push($this->formatChangeRecord($session, $item, 'removed'));
                        }
                        foreach ($diff['changed'] as $item) {
                            $allChanges->push($this->formatChangeRecord($session, $item, 'version_changed'));
                        }
                        foreach ($diff['returned'] as $item) {
                            $allChanges->push($this->formatChangeRecord($session, $item, 'returned'));
                        }
                    }
                }

                $previousSession = $session;
            }
        }

        if (! empty($filters['change_type']) && $filters['change_type'] !== 'All') {
            $allChanges = $allChanges->where('type', $filters['change_type'])->values();
        }

        return $allChanges->sortByDesc('scanned_at')->values();
    }

    /**
     * Format change record for consistent display.
     */
    protected function formatChangeRecord(ScanSession $session, array $item, string $type): array
    {
        return [
            'scan_session_id' => $session->id,
            'computer_id' => $session->computer_id,
            'computer_hostname' => $session->computer?->hostname ?? 'Unknown',
            'laboratory_id' => $session->computer?->laboratory_id,
            'laboratory_name' => $session->computer?->laboratory?->name ?? '-',
            'scanned_at' => $session->started_at,
            'raw_name' => $item['raw_name'],
            'type' => $type,
            'version' => $item['version'] ?? null,
            'old_version' => $item['old_version'] ?? null,
            'new_version' => $item['new_version'] ?? null,
            'vendor' => $item['vendor'] ?? null,
            'session' => $session,
        ];
    }
}
