<?php

namespace App\Services;

use App\Models\HistoryLog;

class HistoryLogService
{
    /**
     * Write a single entry to history_logs.
     *
     * Required keys:
     *   user_id, type_of_report, name, action, designation, designation_type
     *
     * Optional keys:
     *   report_id, updated_field, original_data, updated_data
     */
    public static function log(array $data): void
    {
        HistoryLog::create([
            'user_id'          => $data['user_id']          ?? auth()->id() ?? 0,
            'report_id'        => $data['report_id']        ?? 0,
            'type_of_report'   => $data['type_of_report']   ?? 'General',
            'name'             => $data['name']             ?? 'System',
            'action'           => $data['action']           ?? 'updated',
            'updated_field'    => $data['updated_field']    ?? null,
            'original_data'    => isset($data['original_data'])
                                    ? (is_string($data['original_data'])
                                        ? $data['original_data']
                                        : json_encode($data['original_data']))
                                    : null,
            'updated_data'     => isset($data['updated_data'])
                                    ? (is_string($data['updated_data'])
                                        ? $data['updated_data']
                                        : json_encode($data['updated_data']))
                                    : null,
            'designation'      => $data['designation']      ?? null,
            'designation_type' => $data['designation_type'] ?? null,
        ]);
    }
}
