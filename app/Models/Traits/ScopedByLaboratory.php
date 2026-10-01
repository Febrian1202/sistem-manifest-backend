<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopedByLaboratory
{
    /**
     * Scope query to the laboratory of the given user or current authenticated kepala_lab / staff_lab.
     */
    public function scopeForUserLab(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->hasRole('admin') || $user->hasRole('pimpinan')) {
            return $query;
        }

        if ($user->hasRole('kepala_lab') || $user->hasRole('staff_lab')) {
            $labIds = $user->getAccessibleLaboratoryIds();

            if (empty($labIds)) {
                return $query->whereRaw('1 = 0');
            }

            return $this->scopeForLaboratories($query, $labIds);
        }

        return $query;
    }

    /**
     * Scope query explicitly to multiple laboratory IDs.
     *
     * @param  array<int|string>  $laboratoryIds
     */
    public function scopeForLaboratories(Builder $query, array $laboratoryIds): Builder
    {
        $path = $this->resolveLaboratoryScopeRelation();

        if ($path === null) {
            return $query->whereIn($this->qualifyColumn('laboratory_id'), $laboratoryIds);
        }

        return $query->whereHas($path, function (Builder $q) use ($laboratoryIds) {
            $q->whereIn('laboratory_id', $laboratoryIds);
        });
    }

    /**
     * Scope query explicitly to a specific laboratory ID.
     */
    public function scopeForLaboratory(Builder $query, int|string $laboratoryId): Builder
    {
        return $this->scopeForLaboratories($query, [$laboratoryId]);
    }

    /**
     * Determine the relation path used to scope by laboratory.
     */
    protected function resolveLaboratoryScopeRelation(): ?string
    {
        if (property_exists($this, 'laboratoryScopeRelation') && $this->laboratoryScopeRelation !== null) {
            return $this->laboratoryScopeRelation === 'self' ? null : $this->laboratoryScopeRelation;
        }

        if (method_exists($this, 'scanSession') && ! method_exists($this, 'computer')) {
            return 'scanSession.computer';
        }

        if (method_exists($this, 'computer')) {
            return 'computer';
        }

        return null;
    }
}
