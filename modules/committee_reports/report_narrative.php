<?php
/** Shared report narrative context and optional Gemini draft generation. */

require_once __DIR__ . '/../../includes/GeminiAI.php';

function buildReportNarrative(PDO $pdo, string $type, array $data, int $committeeId = 0): array
{
    $label = ucfirst($type) . ' Report';
    $context = [
        'report_type' => $label,
        'committee_id' => $committeeId ?: null,
        'headers' => $data['headers'],
        'rows' => $data['rows'],
        'record_count' => count($data['rows']),
        'generated_date' => date('Y-m-d'),
    ];
    if ($type === 'performance') {
        $context['performance_data'] = $data['performance_details'] ?? [];
    }

    $fallback = reportNarrativeFallback($type, $data);
    try {
        $ai = new GeminiAI($pdo);
        $draft = $ai->generateReportNarrative($context);
        if ($draft['available']) return $draft['sections'];
    } catch (Throwable $e) {
        error_log('Report narrative generation error: ' . $e->getMessage());
    }
    return $fallback;
}

function reportNarrativeFallback(string $type, array $data): array
{
    $count = count($data['rows']);
    if ($count === 0) {
        $summary = 'Insufficient assignment data available for analysis.';
    } else {
        $summary = 'This draft summarizes ' . $count . ' record(s) retrieved from the CMAS database for the selected report parameters.';
    }

    $narrative = [
        'executive_summary' => $summary,
        'analysis' => $count > 0 ? 'The available assignment records are presented below for review. No additional conclusions are asserted beyond the data shown.' : 'Insufficient assignment data available for analysis.',
        'observations' => $count > 0 ? 'Observations are limited to the assignment records and counts included in the report data.' : 'Insufficient assignment data available for analysis.',
        'recommendations' => $count > 0 ? 'Review the supporting data and apply appropriate administrative or legislative judgment before taking action.' : 'Insufficient data available for this section.',
        'conclusion' => $count > 0 ? 'This report is a CMAS-generated draft for review and does not constitute an official legislative decision.' : 'Insufficient data available for this section.',
    ];
    if ($type === 'performance') {
        $narrative['committee_analysis'] = [];
        $narrative['member_analysis'] = [];
        foreach ($data['performance_details'] ?? [] as $committee) {
            $narrative['committee_analysis'][(int)$committee['committee_id']] = 'Insufficient assignment data available for analysis.';
            foreach ($committee['members'] ?? [] as $member) {
                $narrative['member_analysis'][(int)$member['committee_member_id']] = 'Insufficient assignment data available for analysis.';
            }
        }
    }
    return $narrative;
}
