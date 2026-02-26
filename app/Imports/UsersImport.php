<?php

namespace App\Imports;

use App\Models\Division;
use App\Models\Education;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function __construct(public bool $save = true)
    {
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $divisionName = trim((string) ($row['division'] ?? ''));
        $jobTitleName = trim((string) ($row['job_title'] ?? ''));
        $educationName = trim((string) ($row['education'] ?? ''));

        $division_id = $divisionName !== ''
            ? Division::firstOrCreate(['name' => $divisionName])->id
            : null;

        $job_title_id = $jobTitleName !== ''
            ? JobTitle::firstOrCreate(['name' => $jobTitleName])->id
            : null;

        $education_id = $educationName !== ''
            ? Education::firstOrCreate(['name' => $educationName])->id
            : null;

        $gender = $this->normalizeGender($row['gender'] ?? null);
        if (!$gender) {
            return null;
        }

        $email = mb_strtolower(trim((string) ($row['email'] ?? '')));
        $nip = trim((string) ($row['nip'] ?? ''));

        $attributes = [
            'nip' => $nip,
            'name' => trim((string) ($row['name'] ?? '')),
            'email' => $email,
            'group' => $row['group'] ?? 'user',
            'phone' => trim((string) ($row['phone'] ?? '')),
            'gender' => $gender,
            'basic_salary' => (int) ($row['basic_salary'] ?? 0),
            'hourly_rate' => (int) ($row['hourly_rate'] ?? 0),
            'birth_date' => $row['birth_date'] ?? null,
            'birth_place' => $row['birth_place'] ?? null,
            'address' => $row['address'] ?? '',
            'city' => $row['city'] ?? '',
            'education_id' => $education_id,
            'division_id' => $division_id,
            'job_title_id' => $job_title_id,
            'updated_at' => \Carbon\Carbon::now(),
        ];

        if (!empty($row['password'])) {
            $attributes['password'] = Hash::make((string) $row['password']);
        }

        $existing = User::query()
            ->where('email', $email)
            ->orWhere('nip', $nip)
            ->first();

        if ($existing) {
            if ($this->save) {
                $existing->forceFill($attributes);
                $existing->save();
                \Illuminate\Support\Facades\Log::info('User updated successfully: '.$existing->email);
            }

            return $existing;
        }

        $user = (new User)->forceFill(array_merge($attributes, [
            'id' => !empty($row['id']) ? (string) $row['id'] : null,
            'password' => $attributes['password'] ?? Hash::make((string) ($row['password'] ?? '12345678')),
            'created_at' => $row['created_at'] ?? \Carbon\Carbon::now(),
        ]));

        if ($this->save) {
            $user->save();
            \Illuminate\Support\Facades\Log::info('User imported successfully: ' . $user->email);
        }
        return $user;
    }

    public function rules(): array
    {
        return [
            'nip' => ['required'],
            'name' => ['required', 'string'],
            'email' => ['required', 'string'],
            'phone' => ['nullable'],
            'gender' => ['required', 'string', Rule::in(['male', 'female', 'Male', 'Female', 'MALE', 'FEMALE', 'L', 'P', 'l', 'p'])],
            'password' => ['nullable', 'string'],
        ];
    }

    private function normalizeGender(mixed $value): ?string
    {
        $normalized = mb_strtolower(trim((string) $value));

        return match ($normalized) {
            'male', 'l' => 'male',
            'female', 'p' => 'female',
            default => null,
        };
    }
}
