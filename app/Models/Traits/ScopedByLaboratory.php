<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopedByLaboratory
{
    /**
     * Scope query to the laboratory of the given user or current authenticated kepala_lab.
     */
    public function scopeForUserLab(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->hasRole('kepala_lab')) {
            if (! $user->laboratory_id) {
                return $query->whereRaw('1 = 0');
            }

            return $this->scopeForLaboratory($query, $user->laboratory_id);
        }

        return $query;
    }

    /**
     * Scope query explicitly to a specific laboratory ID.
     */
    public function scopeForLaboratory(Builder $query, int|string $laboratoryId): Builder
    {
        $path = $this->resolveLaboratoryScopeRelation();

        if ($path === null) {
            return $query->where($this->qualifyColumn('laboratory_id'), $laboratoryId);
        }

        return $query->whereHas($path, function (Builder $q) use ($laboratoryId) {
            $q->where('laboratory_id', $laboratoryId);
        });
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
