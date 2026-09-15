<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up()
    {
        $permission_names = ['fixed_asset.view', 'fixed_asset.manage'];

        $existing_permissions = Permission::whereIn('name', $permission_names)
                                    ->pluck('name')
                                    ->toArray();

        foreach ($permission_names as $permission_name) {
            if (! in_array($permission_name, $existing_permissions)) {
                Permission::create(['name' => $permission_name]);
            }
        }
    }

    public function down()
    {
        //
    }
};
