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
            'crm_lead.view_all', 'crm_lead.view_own', 'crm_lead.create', 'crm_lead.update', 'crm_lead.delete',
            'crm_pipeline.manage', 'crm_activity.manage',
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
