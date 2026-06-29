<?php

namespace App\Http\Controllers\Resorts\Concerns;

use App\Exports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Shared Export (CSV / Excel / PDF) + WAI Insights for the predefined report
 * controllers (Workforce Planning, Payroll). These reports are computed on the
 * fly (no saved ResortReports row), so this works purely off a name + the
 * { columns, rows } the report produced — mirroring ReportController's saved-report
 * export/insight behaviour without the DB-cached AiInsights.
 *
 * Requires the using class to expose $this->resort (the resort-admin user).
 */
trait PredefinedReportActions
{
    /** Build a CSV / Excel / PDF download from computed report data. */
    protected function exportComputedReport(string $name, ?string $description, array $columns, array $rows, string $format)
    {
        $insights = $this->computeAiInsightsText($name, $description, $columns, $rows);

        switch ($format) {
            case 'pdf':
                $pdf = Pdf::loadView('resorts.reports.pdf', [
                    'report'       => (object) ['name' => $name],
                    'data'         => $rows,
                    'columns'      => $columns,
                    'insightsHtml' => $insights !== '' ? $this->markdownToHtmlSimple($insights) : '',
                ]);
                return $pdf->download($name . '.pdf');

            case 'excel':
                return Excel::download(new ReportExport($rows, $columns, $this->markdownToLinesSimple($insights)), $name . '.xlsx');

            case 'csv':
                return Excel::download(new ReportExport($rows, $columns, $this->markdownToLinesSimple($insights)), $name . '.csv');

            default:
                abort(400, 'Unsupported export format');
        }
    }

    /**
     * Call the WAI report-analysis service for computed data and return the
     * markdown analysis. Degrades to '' if the service is unset/unreachable so
     * callers never break.
     */
    protected function computeAiInsightsText(string $name, ?string $description, array $columns, array $rows): string
    {
        $url = env('AI_Report_fetch_URL');
        if (!$url || empty($rows)) {
            return '';
        }

        $reportInfo = [
            'name'        => $name,
            'resort_id'   => optional($this->resort)->resort_id,
            'description' => $description,
            'created_at'  => now()->format('d/m/Y'),
        ];
        $formatted = [];
        foreach ($rows as $row) {
            $row['report'] = $reportInfo;
            $formatted[]   = $row;
        }
        $payload = ['resort_data' => ['additionalProp1' => ['columns' => $columns, 'data' => $formatted]]];
        $json    = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($json),
            ],
        ]);
        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return '';
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? (string) ($decoded['analysis'] ?? '') : '';
    }

    /**
     * Append a "Total" footer row summing the numeric columns. Skipped when there
     * are fewer than 2 data rows (single-row summaries are already totals) or when
     * no column is numeric. Percentage and text columns are left blank in the total.
     */
    protected function appendTotalsRow(array $columns, array $rows): array
    {
        if (count($rows) < 2) {
            return $rows;
        }

        $isNumeric = fn($v) => is_string($v) && preg_match('/^-?[\d,]+(\.\d+)?$/', trim($v));
        $total = [];
        $anyNumeric = false;

        foreach ($columns as $i => $col) {
            if ($i === 0) { $total[$col] = 'Total'; continue; }

            $values = array_map(fn($r) => (string) ($r[$col] ?? ''), $rows);
            // Percentage columns can't be summed meaningfully.
            if (count(array_filter($values, fn($v) => strpos($v, '%') !== false))) {
                $total[$col] = '';
                continue;
            }
            $nums = array_filter($values, $isNumeric);
            if (!count($nums)) {
                $total[$col] = '';
                continue;
            }
            $anyNumeric = true;
            $hasDecimal = (bool) count(array_filter($nums, fn($v) => strpos($v, '.') !== false));
            $sum = array_sum(array_map(fn($v) => (float) str_replace(',', '', $v), $nums));
            $total[$col] = $hasDecimal ? number_format($sum, 2) : number_format($sum);
        }

        if (!$anyNumeric) {
            return $rows; // nothing to total (all text columns)
        }

        $rows[] = $total;
        return $rows;
    }

    /** Minimal Markdown -> HTML for the PDF insights block (#headings, **bold**, - lists). */
    protected function markdownToHtmlSimple(string $md): string
    {
        $lines  = preg_split('/\r\n|\r|\n/', $md);
        $html   = '';
        $inList = false;
        $inline = fn($t) => preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', e($t));

        foreach ($lines as $line) {
            $t = rtrim($line);
            if (preg_match('/^\s*[-*]\s+(.*)/', $t, $m)) {
                if (!$inList) { $html .= '<ul>'; $inList = true; }
                $html .= '<li>' . $inline($m[1]) . '</li>';
                continue;
            }
            if ($inList) { $html .= '</ul>'; $inList = false; }
            if (preg_match('/^(#{1,4})\s+(.*)/', $t, $m)) {
                $lvl = strlen($m[1]);
                $html .= "<h{$lvl}>" . $inline($m[2]) . "</h{$lvl}>";
                continue;
            }
            if (trim($t) === '') continue;
            $html .= '<p>' . $inline($t) . '</p>';
        }
        if ($inList) { $html .= '</ul>'; }
        return $html;
    }

    /** Markdown -> plain lines for the spreadsheet insights sheet. */
    protected function markdownToLinesSimple(string $md): array
    {
        if ($md === '') return [];
        return array_values(array_filter(
            array_map(fn($l) => trim(preg_replace('/[#*]/', '', $l)), preg_split('/\r\n|\r|\n/', $md)),
            fn($l) => $l !== ''
        ));
    }
}
