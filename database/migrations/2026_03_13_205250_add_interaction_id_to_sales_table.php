<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Agregamos la columna interaction_id justo después de lead_id
            $table->unsignedBigInteger('interaction_id')->after('lead_id');
            
            // Creamos la relación (Llave foránea)
            $table->foreign('interaction_id')
                  ->references('id')
                  ->on('interactions')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Primero borramos la relación, luego la columna
            $table->dropForeign(['interaction_id']);
            $table->dropColumn('interaction_id');
        });
    }
};
