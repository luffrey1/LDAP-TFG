<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clase_grupos', function (Blueprint $table) {
            // Eliminar la columna profesor_ldap_dn si existe
            if (Schema::hasColumn('clase_grupos', 'profesor_ldap_dn')) {
                $table->dropColumn('profesor_ldap_dn');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clase_grupos', function (Blueprint $table) {
            // Añadir la columna profesor_ldap_dn en caso de rollback
            if (!Schema::hasColumn('clase_grupos', 'profesor_ldap_dn')) {
                $table->string('profesor_ldap_dn')->nullable()->after('profesor_id')
                    ->comment('Distinguished Name del profesor en LDAP');
            }
        });
    }
};
