<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission_names = [
            'employee.view', 'employee.create', 'employee.update', 'employee.delete',
            'attendance.view', 'attendance.manage',
            'leave_type.manage',
            'leave_request.view_own', 'leave_request.view_all', 'leave_request.approve',
            'payroll.view', 'payroll.manage', 'payroll.approve', 'payroll.post_to_ledger',
        ];

        $existing_permissions = Permission::whereIn('name', $permission_names)
                                    ->pluck('name')
                                    ->toArray();

        foreach ($permission_names as $permission_name) {
            if (! in_array($permission_name, $existing_permissions)) {
                Permission::create(['name' => $permission_name]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
