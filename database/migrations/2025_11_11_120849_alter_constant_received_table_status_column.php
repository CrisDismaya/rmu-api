<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
            DECLARE @ConstraintName NVARCHAR(200);
            DECLARE @SQL NVARCHAR(MAX);

            SELECT @ConstraintName = name
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('recieve_unit_details')
              AND definition LIKE '%[status]%';

            IF @ConstraintName IS NOT NULL
            BEGIN
                SET @SQL = 'ALTER TABLE recieve_unit_details DROP CONSTRAINT [' + @ConstraintName + ']';
                EXEC sp_executesql @SQL;
            END;

            ALTER TABLE recieve_unit_details
            ADD CONSTRAINT CK_recieve_unit_details_status
            CHECK ([status] IN ('0','1','2','4','5'));
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared("
            DECLARE @ConstraintName NVARCHAR(200);
            DECLARE @SQL NVARCHAR(MAX);

            SELECT @ConstraintName = name
            FROM sys.check_constraints
            WHERE parent_object_id = OBJECT_ID('recieve_unit_details')
              AND definition LIKE '%[status]%';

            IF @ConstraintName IS NOT NULL
            BEGIN
                SET @SQL = 'ALTER TABLE recieve_unit_details DROP CONSTRAINT [' + @ConstraintName + ']';
                EXEC sp_executesql @SQL;
            END;

            ALTER TABLE recieve_unit_details
            ADD CONSTRAINT CK_recieve_unit_details_status
            CHECK ([status] IN ('0','1','2','4'));
        ");
    }
};
