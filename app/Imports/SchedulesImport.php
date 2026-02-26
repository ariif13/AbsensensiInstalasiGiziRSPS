<?php

namespace App\Imports;

use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SchedulesImport implements ToCollection, WithHeadingRow
{
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $importErrors = [];
    public array $previewRows = [];

    public function __construct(public bool $save = true)
    {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $email = trim((string) ($row['email'] ?? ''));
            $name = trim((string) ($row['nama'] ?? $row['name'] ?? ''));

            $user = $this->resolveUser($email, $name, $rowNumber);
            if (!$user) {
                continue;
            }

            $month = $this->parseMonth($row['bulan'] ?? $row['month'] ?? null);
            $year = (int) ($row['tahun'] ?? $row['year'] ?? 0);

            if (!$month || $month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
                $this->importErrors[] = "Row {$rowNumber}: Bulan/Tahun tidak valid.";
                $this->skippedCount++;
                continue;
            }

            for ($day = 1; $day <= 31; $day++) {
                $key = (string) $day;
                $rawCode = trim((string) ($row[$key] ?? ''));

                if ($rawCode === '') {
                    continue;
                }

                if (!checkdate($month, $day, $year)) {
                    continue;
                }

                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $resolved = $this->resolveShift($rawCode);

                if (!$resolved['ok']) {
                    $this->importErrors[] = "Row {$rowNumber}, tanggal {$day}: kode '{$rawCode}' tidak dikenali.";
                    $this->skippedCount++;
                    continue;
                }

                if ($this->save) {
                    Schedule::updateOrCreate(
                        ['user_id' => $user->id, 'date' => $date],
                        [
                            'shift_id' => $resolved['shift_id'],
                            'is_off' => $resolved['is_off'],
                        ]
                    );
                }

                if (count($this->previewRows) < 500) {
                    $this->previewRows[] = [
                        'date' => $date,
                        'user' => $user->name,
                        'email' => $user->email,
                        'code' => strtoupper($rawCode),
                        'shift' => $resolved['label'],
                    ];
                }

                $this->importedCount++;
            }
        }
    }

    private function resolveUser(string $email, string $name, int $rowNumber): ?User
    {
        if ($email !== '') {
            $user = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
            if ($user) {
                return $user;
            }
        }

        if ($name === '') {
            $this->importErrors[] = "Row {$rowNumber}: Email/Nama wajib diisi.";
            $this->skippedCount++;
            return null;
        }

        $matchedByName = User::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->get();

        if ($matchedByName->count() === 1) {
            return $matchedByName->first();
        }

        if ($matchedByName->count() > 1) {
            $this->importErrors[] = "Row {$rowNumber}: Nama '{$name}' duplikat, gunakan email.";
            $this->skippedCount++;
            return null;
        }

        $this->importErrors[] = "Row {$rowNumber}: User tidak ditemukan (email/nama).";
        $this->skippedCount++;
        return null;
    }

    private function resolveShift(string $code): array
    {
        $normalized = mb_strtoupper(trim($code));

        if (in_array($normalized, ['OFF', 'L', 'LIBUR'], true)) {
            return [
                'ok' => true,
                'is_off' => true,
                'shift_id' => null,
                'label' => 'OFF',
            ];
        }

        $nameCandidates = match ($normalized) {
            'P' => ['pagi', 'p'],
            'S' => ['siang', 's'],
            'M' => ['malam', 'm'],
            default => [mb_strtolower($normalized)],
        };

        $shift = $this->findShiftByCandidates($nameCandidates);

        if (!$shift) {
            return [
                'ok' => false,
                'is_off' => false,
                'shift_id' => null,
                'label' => '',
            ];
        }

        return [
            'ok' => true,
            'is_off' => false,
            'shift_id' => $shift->id,
            'label' => $shift->name,
        ];
    }

    private function findShiftByCandidates(array $candidates): ?Shift
    {
        foreach ($candidates as $candidate) {
            $exact = Shift::query()->whereRaw('LOWER(name) = ?', [$candidate])->first();
            if ($exact) {
                return $exact;
            }
        }

        foreach ($candidates as $candidate) {
            $startsWith = Shift::query()->whereRaw('LOWER(name) LIKE ?', [$candidate.'%'])->first();
            if ($startsWith) {
                return $startsWith;
            }
        }

        return null;
    }

    private function parseMonth(mixed $value): ?int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $month = mb_strtolower(trim((string) $value));

        $months = [
            'januari' => 1,
            'january' => 1,
            'februari' => 2,
            'february' => 2,
            'maret' => 3,
            'march' => 3,
            'april' => 4,
            'mei' => 5,
            'may' => 5,
            'juni' => 6,
            'june' => 6,
            'juli' => 7,
            'july' => 7,
            'agustus' => 8,
            'august' => 8,
            'september' => 9,
            'oktober' => 10,
            'october' => 10,
            'november' => 11,
            'desember' => 12,
            'december' => 12,
        ];

        return $months[$month] ?? null;
    }
}
