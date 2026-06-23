<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->remapLegacyLeadStatuses();
        $this->remapLegacyInteractionStatuses();
    }

    public function down(): void
    {
        // Forward-only migration: the legacy states were intentionally retired.
    }

    private function remapLegacyLeadStatuses(): void
    {
        if (!Schema::hasTable('leads') || !Schema::hasColumn('leads', 'status_specific')) {
            return;
        }

        if (Schema::hasColumn('leads', 'status_final')) {
            DB::table('leads')
                ->where('status_specific', 'si_verbal')
                ->update([
                    'status_final' => 'en_seguimiento',
                ]);
        }

        DB::table('leads')
            ->whereIn('status_specific', ['interesado', 'si_verbal'])
            ->update([
                'status_specific' => 'negociacion',
            ]);
    }

    private function remapLegacyInteractionStatuses(): void
    {
        if (!Schema::hasTable('interactions')) {
            return;
        }

        $hasStatus = Schema::hasColumn('interactions', 'status');
        $hasStatusSpecific = Schema::hasColumn('interactions', 'status_specific');

        if (!$hasStatus && !$hasStatusSpecific) {
            return;
        }

        $siVerbalUpdate = [];

        if ($hasStatus) {
            $siVerbalUpdate['status'] = 'negociacion';
        }

        if ($hasStatusSpecific) {
            $siVerbalUpdate['status_specific'] = 'negociacion';
        }

        if (Schema::hasColumn('interactions', 'is_agreement')) {
            $siVerbalUpdate['is_agreement'] = false;
        }

        if (Schema::hasColumn('interactions', 'agreed_at')) {
            $siVerbalUpdate['agreed_at'] = null;
        }

        if ($siVerbalUpdate !== []) {
            DB::table('interactions')
                ->where(function ($query) use ($hasStatus, $hasStatusSpecific) {
                    if ($hasStatus) {
                        $query->where('status', 'si_verbal');
                    }

                    if ($hasStatusSpecific) {
                        $method = $hasStatus ? 'orWhere' : 'where';
                        $query->{$method}('status_specific', 'si_verbal');
                    }
                })
                ->update($siVerbalUpdate);
        }

        $interesadoUpdate = [];

        if ($hasStatus) {
            $interesadoUpdate['status'] = 'negociacion';
        }

        if ($hasStatusSpecific) {
            $interesadoUpdate['status_specific'] = 'negociacion';
        }

        if ($interesadoUpdate !== []) {
            DB::table('interactions')
                ->where(function ($query) use ($hasStatus, $hasStatusSpecific) {
                    if ($hasStatus) {
                        $query->where('status', 'interesado');
                    }

                    if ($hasStatusSpecific) {
                        $method = $hasStatus ? 'orWhere' : 'where';
                        $query->{$method}('status_specific', 'interesado');
                    }
                })
                ->update($interesadoUpdate);
        }
    }
};
