<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserExport implements FromQuery, WithHeadings
{
    use Exportable;

    public function __construct(public ?string $search = null) {}

    public function headings(): array
    {
        return [
            'email',
            'first_name',
            'last_name',
            'country',
            'phone',
            'job',
            'active',
        ];
    }

    public function query()
    {
        $query = User::query();

        $query->when($this->search, function ($query) {
            $query->where('first_name', 'LIKE', "%$this->search%");
            $query->orWhere('last_name', 'LIKE', "%$this->search%");
            $query->orWhere('country', 'LIKE', "%$this->search%");
            $query->orWhere('phone', 'LIKE', "%$this->search%");
            $query->orWhere('job', 'LIKE', "%$this->search%");
            $query->orWhere('email', 'LIKE', "%$this->search%");
        });

        $query->select([
            'email',
            'first_name',
            'last_name',
            'country',
            'phone',
            'job',
            'active',
        ]);

        return $query;
    }
}
