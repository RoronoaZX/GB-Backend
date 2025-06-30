<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employment_type_id',
        'firstname',
        'middlename',
        'lastname',
        'birthdate',
        'phone',
        'address',
        'sex',
        'position',
        'status'
    ];

     /**
     * The attributes that should be hidden for serialization.
     * This will remove 'branchEmployee' and 'warehouseEmployee' from the final JSON.
     *
     * @var array<int, string>
     */
    // protected $hidden = [
    //     'branchEmployee',
    //     'warehouseEmployee',
    // ];

   /**
     * The accessors to append to the model's array form.
     * We now only need to append our new unified 'designation' attribute.
     *
     * @var array<int, string>
     */
    protected $appends = ['designation'];


    // --- Existing Relationships ---

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }

    public function userDesignation()
    {
        return $this->hasOne(User::class, 'employee_id', 'id');
    }

    public function branchEmployee()
    {
        return $this->hasOne(BranchEmployee::class, 'employee_id','id');
    }

    public function warehouseEmployee()
    {
        return $this->hasOne(WarehouseEmployee::class, 'employee_id','id');
    }

    public function salesReports()
    {
        return $this->hasMany(SalesReports::class);
    }
    public function employmentType()
    {
        return $this->belongsTo(EmploymentType::class, 'employment_type_id', 'id');
    }
    public function branch()
    {
        // Note: This relationship seems to imply an Employee can manage a branch.
        // The designation logic will use the 'branchEmployee' relationship instead.
        return $this->hasOne(Branch::class, 'employee_id', 'id');
    }

    // --- NEW ACCESSORS FOR DESIGNATION ---

     /**
     * Get the employee's designation object (either a Branch or a Warehouse).
     *
     * This accessor checks the loaded relationships and returns the full
     * designation object (Branch or Warehouse), or null if none is found.
     * It relies on using `with('branchEmployee.branch', 'warehouseEmployee.warehouse')`
     * in your controller query for efficiency.
     *
     * @return Model|null
     *
     *
     */

    // public function getDesignationAttribute(): ?Model
    // {
    //     if ($this->relationLoaded('branchEmployee') && $this->branchEmployee) {
    //         $branch = $this->branchEmployee->branch;
    //         if ($branch) {
    //             $branch->time_in = $this->branchEmployee->time_in;
    //             $branch->time_out = $this->branchEmployee->time_out;
    //         }
    //         return $branch;
    //     }

    //     if ($this->relationLoaded('warehouseEmployee') && $this->warehouseEmployee) {
    //         $warehouse = $this->warehouseEmployee->warehouse;
    //         if ($warehouse) {
    //             $warehouse->time_in = $this->warehouseEmployee->time_in;
    //             $warehouse->time_out = $this->warehouseEmployee->time_out;
    //         }
    //         return $warehouse;
    //     }

    //     return null;
    // }


     public function getDesignationAttribute(): ?Model
    {
        // If employee is assigned to a branch
        if ($this->relationLoaded('branchEmployee') && $this->branchEmployee) {
            $branch = $this->branchEmployee->branch;

            // Attach time_in and time_out from branchEmployee
            if ($branch) {
                $branch->time_in = $this->branchEmployee->time_in;
                $branch->time_out = $this->branchEmployee->time_out;
                $branch->designation_type_id = $this->branchEmployee->id;
                $branch->designation_type = 'branch';
            }

            return $branch;
        }

        // If employee is assigned to a warehouse
        if ($this->relationLoaded('warehouseEmployee') && $this->warehouseEmployee) {
            $warehouse = $this->warehouseEmployee->warehouse;

            // Attach time_in and time_out from warehouseEmployee
            if ($warehouse) {
                $warehouse->time_in = $this->warehouseEmployee->time_in;
                $warehouse->time_out = $this->warehouseEmployee->time_out;
                $warehouse->designation_type_id = $this->warehouseEmployee->id;
                $warehouse->designation_type = 'warehouse';
            }

            return $warehouse;
        }

        return null;
    }


    // public function getDesignationAttribute(): ?Model
    // {
    //     // Check if the branch relationship is loaded and exists
    //     if ($this->relationLoaded('branchEmployee') && $this->branchEmployee) {
    //         // Return the entire branch object. The ?-> is a nullsafe operator.
    //         return $this->branchEmployee->branch;
    //     }

    //     // Otherwise, check if the warehouse relationship is loaded and exists
    //     if ($this->relationLoaded('warehouseEmployee') && $this->warehouseEmployee) {
    //         // Return the entire warehouse object
    //         return $this->warehouseEmployee->warehouse;
    //     }

    //     // If no designation is found, return null
    //     return null;
    // }
}
