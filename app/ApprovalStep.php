<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'is_final' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(\App\ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function approver_user()
    {
        return $this->belongsTo(\App\User::class, 'approver_id');
    }

    /**
     * Checks whether the given user is allowed to act on this step.
     */
    public function canBeActedOnBy(User $user)
    {
        if ($this->approver_type == 'user') {
            return $this->approver_id == $user->id;
        }

        if ($this->approver_type == 'role') {
            $role = \Spatie\Permission\Models\Role::find($this->approver_id);

            return ! empty($role) && $user->hasRole($role->name);
        }

        if ($this->approver_type == 'permission') {
            return $user->can($this->permission_name);
        }

        return false;
    }
}
