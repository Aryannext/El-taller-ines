<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// ADR-007: los avisos automáticos salen por Evolution API y la constancia debe decir ese canal (RN-41)
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE avisos MODIFY canal ENUM('api_oficial', 'evolution_api', 'asistido') NULL COMMENT 'Canal por el que salió el aviso (RN-40, ADR-003, ADR-007)'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE avisos MODIFY canal ENUM('api_oficial', 'asistido') NULL COMMENT 'Canal por el que salió el aviso (RN-40, ADR-003)'");
    }
};
