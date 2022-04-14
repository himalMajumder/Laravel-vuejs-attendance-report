<?php

namespace App\Imports;

use App\Models\EmployeeAttendance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AttendanceImport implements
ToCollection,
WithHeadingRow,
SkipsOnError,
WithValidation,
SkipsOnFailure,
WithChunkReading,
ShouldQueue,
WithEvents {
    use Importable, SkipsErrors, SkipsFailures, RegistersEventListeners;

    public function collection(Collection $rows) {

        foreach ($rows as $row) {
            EmployeeAttendance::create([
                "month"         => $row['month'],
                "date"          => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(intval($row['date']))->format('Y-m-d'),
                "day"           => $row['day'],
                "employee_id"            => $row['id'],
                "employee_name" => $row['employee_name'],
                "department"    => $row['department'],
                "first_in_time" => $this->formatDateTimeCell($row['first_in_time']),
                "last_out_time" => $this->formatDateTimeCell($row['last_out_time']),
                "hours_of_work" => $row['hours_of_work'],
            ]);

        }

    }

    public function rules(): array
    {
        return [];
    }

    public function chunkSize(): int {
        return 1000;
    }

/**
 *
 * Convert excel-timestamp to Php-timestamp and again to excel-timestamp to compare both compare
 * By Leonardo J. Jauregui ( @Nanod10 | siskit dot com )
 *
 * @param $value (cell value)
 * @param String $datetime_output_format
 * @param String $date_output_format
 * @param String $time_output_format
 * ref: https://stackoverflow.com/questions/37044353/laravel-excel-import-date-column-in-excel-cell-returns-as-floating-value-how-t
 *
 * @return $formatedCellValue
 */
    private function formatDateTimeCell($value, $datetime_output_format = "H:i:s", $date_output_format = "Y-m-d", $time_output_format = "H:i:s") {

        $is_only_time            = false;
        $excel_datetime_exploded = explode(".", $value);

        if (strstr($value, ".")) {
            $dateTimeObject = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);

            if (intval($excel_datetime_exploded[0]) > 0) {
                $output_format = $datetime_output_format;
                $is_only_time  = false;
            } else {
                $output_format = $time_output_format;
                $is_only_time  = true;
            }

        } else {
            $dateTimeObject = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            $output_format  = $date_output_format;
            $is_only_time   = false;
        }

        $phpTimestamp   = $dateTimeObject->getTimestamp();
        $excelTimestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($phpTimestamp);

        if ($is_only_time) {
            $excelTimestamp = $excelTimestamp - 25569;
        }

        if (floatval($value) === $excelTimestamp) {
            $formatedCellValue = $dateTimeObject->format($output_format);
            return $formatedCellValue;
        } else {
            return false;
        }

    }

}
